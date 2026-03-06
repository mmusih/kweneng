<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('user', 'currentClass');
        
        // Apply search filter if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })->orWhere('admission_no', 'like', "%{$search}%");
            });
        }
        
        // Apply class filter if provided
        if ($request->filled('class_id')) {
            $query->where('current_class_id', $request->class_id);
        }
        
        // Paginate results
        $students = $query->paginate(15);
        $classes = ClassModel::all();
        
        return view('admin.students.index', compact('students', 'classes'));
    }

    public function create()
    {
        $classes = ClassModel::all();
        return view('admin.students.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'admission_no' => 'required|unique:students,admission_no',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'current_class_id' => 'nullable|exists:classes,id',
            'photo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'results_access' => 'nullable',
            'fees_blocked' => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            // Create user account
$user = User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'password' => Hash::make('password'), // Changed from Str::random(12) to 'password'
    'role' => 'student',
    'status' => 'active',
]);


            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students', 'public');
            }

            // Create student record
            $student = Student::create([
                'user_id' => $user->id,
                'admission_no' => $validated['admission_no'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'current_class_id' => $validated['current_class_id'] ?? null,
                'photo' => $photoPath,
                'results_access' => $request->has('results_access'),
                'fees_blocked' => $request->has('fees_blocked'),
            ]);

            // AUTOMATICALLY ENROLL STUDENT IF CLASS IS SELECTED
            if ($validated['current_class_id']) {
                // Get the current active academic year
                $currentAcademicYear = AcademicYear::where('status', 'open')->first();
                
                if ($currentAcademicYear) {
                    StudentClassHistory::create([
                        'student_id' => $student->id,
                        'class_id' => $validated['current_class_id'],
                        'academic_year_id' => $currentAcademicYear->id,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.students.index')
                            ->with('success', 'Student created successfully' . 
                                   ($validated['current_class_id'] ? ' and enrolled in class' : ''));

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                            ->withErrors(['error' => 'Failed to create student: ' . $e->getMessage()])
                            ->withInput();
        }
    }

    public function show(Student $student)
    {
        // Load relationships for the student profile
        $student->load([
            'user',
            'currentClass.academicYear',
            'classHistory.class',
            'classHistory.academicYear'
        ]);
        
        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $classes = ClassModel::all();
        return view('admin.students.edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($student->user_id)],
            'admission_no' => ['required', Rule::unique('students', 'admission_no')->ignore($student->id)],
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'current_class_id' => 'nullable|exists:classes,id',
            'photo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'results_access' => 'nullable',
            'fees_blocked' => 'nullable',
        ]);

        try {
            DB::beginTransaction();

            // Update user
            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }
                $photoPath = $request->file('photo')->store('students', 'public');
                $validated['photo'] = $photoPath;
            } else {
                unset($validated['photo']);
            }

            // Store original class ID for comparison
            $originalClassId = $student->current_class_id;

            // Update student
            $studentData = [
                'admission_no' => $validated['admission_no'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'current_class_id' => $validated['current_class_id'] ?? null,
                'results_access' => $request->has('results_access'),
                'fees_blocked' => $request->has('fees_blocked'),
            ];

            // Add photo to update data if it was uploaded
            if (isset($validated['photo'])) {
                $studentData['photo'] = $validated['photo'];
            }

            // Update student
            $student->update($studentData);

            // HANDLE CLASS CHANGE - Update enrollment if class changed
            if ($validated['current_class_id'] && $validated['current_class_id'] != $originalClassId) {
                // Get the current active academic year
                $currentAcademicYear = AcademicYear::where('status', 'open')->first();
                
                if ($currentAcademicYear) {
                    // Check if student already has enrollment for this year
                    $existingEnrollment = StudentClassHistory::where('student_id', $student->id)
                        ->where('academic_year_id', $currentAcademicYear->id)
                        ->first();
                    
                    if ($existingEnrollment) {
                        // Update existing enrollment
                        $existingEnrollment->update([
                            'class_id' => $validated['current_class_id'],
                            'status' => 'active',
                            'updated_at' => now(),
                        ]);
                    } else {
                        // Create new enrollment
                        StudentClassHistory::create([
                            'student_id' => $student->id,
                            'class_id' => $validated['current_class_id'],
                            'academic_year_id' => $currentAcademicYear->id,
                            'status' => 'active',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.students.index')
                            ->with('success', 'Student updated successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                            ->withErrors(['error' => 'Failed to update student: ' . $e->getMessage()])
                            ->withInput();
        }
    }

    public function destroy(Student $student)
    {
        try {
            DB::beginTransaction();

            // Delete photo if exists
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }

            // Delete enrollment records first
            StudentClassHistory::where('student_id', $student->id)->delete();

            // Delete the user account (this will also delete the student due to foreign key constraint)
            $student->user->delete();

            DB::commit();

            return redirect()->route('admin.students.index')
                            ->with('success', 'Student deleted successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                            ->withErrors(['error' => 'Failed to delete student: ' . $e->getMessage()]);
        }
    }
}
