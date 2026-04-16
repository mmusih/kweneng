<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\User;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use App\Models\StudentClassHistory;
use App\Models\StudentSubject;
use App\Models\Mark;
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

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('current_class_id', $request->class_id);
        }

        $students = $query
            ->latest()
            ->paginate(15)
            ->appends($request->query());

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

            $temporaryPassword = $this->generateTemporaryPassword();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'role' => 'student',
                'status' => 'active',
            ]);

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('students', 'public');
            }

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

            if (!empty($validated['current_class_id'])) {
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
                ->with(
                    'success',
                    'Student created successfully' .
                        (!empty($validated['current_class_id']) ? ' and enrolled in class.' : '.') .
                        ' Temporary password: ' . $temporaryPassword .
                        ' (Student must change it on first login.)'
                );
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Failed to create student: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Student $student)
    {
        $student->load([
            'user',
            'currentClass.academicYear',
            'classHistory.class',
            'classHistory.academicYear',
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

            $student->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($request->hasFile('photo')) {
                if ($student->photo) {
                    Storage::disk('public')->delete($student->photo);
                }

                $photoPath = $request->file('photo')->store('students', 'public');
                $validated['photo'] = $photoPath;
            } else {
                unset($validated['photo']);
            }

            $originalClassId = $student->current_class_id;

            $studentData = [
                'admission_no' => $validated['admission_no'],
                'gender' => $validated['gender'],
                'date_of_birth' => $validated['date_of_birth'],
                'current_class_id' => $validated['current_class_id'] ?? null,
                'results_access' => $request->has('results_access'),
                'fees_blocked' => $request->has('fees_blocked'),
            ];

            if (isset($validated['photo'])) {
                $studentData['photo'] = $validated['photo'];
            }

            $student->update($studentData);

            if (!empty($validated['current_class_id']) && $validated['current_class_id'] != $originalClassId) {
                $currentAcademicYear = AcademicYear::where('status', 'open')->first();

                if ($currentAcademicYear) {
                    $existingEnrollment = StudentClassHistory::where('student_id', $student->id)
                        ->where('academic_year_id', $currentAcademicYear->id)
                        ->first();

                    if ($existingEnrollment) {
                        $existingEnrollment->update([
                            'class_id' => $validated['current_class_id'],
                            'status' => 'active',
                            'updated_at' => now(),
                        ]);
                    } else {
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
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Failed to update student: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function resetPassword(Student $student)
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        $student->user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return redirect()->back()->with(
            'success',
            'Password reset successfully. Temporary password: ' . $temporaryPassword . ' (Student must change it on first login.)'
        );
    }

    public function destroy(Request $request, Student $student)
    {
        try {
            DB::beginTransaction();

            $this->deleteStudentSafely($student);

            DB::commit();

            return redirect()->route('admin.students.index', $request->only([
                'search',
                'class_id',
                'page',
            ]))->with('success', 'Student deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete student: ' . $e->getMessage()]);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ], [
            'student_ids.required' => 'Please select at least one student.',
            'student_ids.min' => 'Please select at least one student.',
        ]);

        try {
            $studentIds = collect($validated['student_ids'])
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values();

            DB::beginTransaction();

            $students = Student::with('user')
                ->whereIn('id', $studentIds)
                ->get();

            foreach ($students as $student) {
                $this->deleteStudentSafely($student);
            }

            DB::commit();

            return redirect()->route('admin.students.index', $request->only([
                'search',
                'class_id',
                'page',
            ]))->with('success', $students->count() . ' student(s) deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => 'Bulk delete failed: ' . $e->getMessage()]);
        }
    }

    private function deleteStudentSafely(Student $student): void
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        StudentClassHistory::where('student_id', $student->id)->delete();
        StudentSubject::where('student_id', $student->id)->delete();
        Mark::where('student_id', $student->id)->delete();

        if ($student->user) {
            $student->user->delete();
        } else {
            $student->delete();
        }
    }

    private function generateTemporaryPassword(int $length = 10): string
    {
        return Str::upper(Str::random($length));
    }
}
