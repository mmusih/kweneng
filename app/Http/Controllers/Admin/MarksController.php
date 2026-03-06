<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MarksService;
use App\Models\Mark;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\StudentSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarksController extends Controller
{
    protected $marksService;

    public function __construct(MarksService $marksService)
    {
        $this->marksService = $marksService;
    }

    public function index(Request $request)
    {
        $query = Mark::with(['student.user', 'subject', 'class', 'teacher.user', 'academicYear', 'term']);

        // Apply filters
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        if ($request->filled('term_id')) {
            $query->where('term_id', $request->term_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $marks = $query->latest()->paginate(50);

        // Get filter options
        $academicYears = AcademicYear::all();
        $classes = ClassModel::all();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();

        return view('admin.marks.index', compact('marks', 'academicYears', 'classes', 'subjects', 'teachers'));
    }

    public function show($id)
    {
        $mark = Mark::with(['student.user', 'subject', 'class', 'teacher.user', 'academicYear', 'term'])->findOrFail($id);
        return view('admin.marks.show', compact('mark'));
    }

    public function edit($id)
    {
        $mark = Mark::findOrFail($id);
        
        if (!Gate::allows('override', $mark)) {
            abort(403);
        }

        return view('admin.marks.edit', compact('mark'));
    }

    public function update(Request $request, $id)
    {
        $mark = Mark::findOrFail($id);
        
        if (!Gate::allows('override', $mark)) {
            abort(403);
        }

        $validated = $request->validate([
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'endterm_score' => 'nullable|numeric|min:0|max:100',
            'grade' => 'nullable|string|max:2',
            'remarks' => 'nullable|string|max:500'
        ]);

        // Recalculate grade if scores changed
        if (isset($validated['midterm_score']) || isset($validated['endterm_score'])) {
            $midterm = $validated['midterm_score'] ?? $mark->midterm_score;
            $endterm = $validated['endterm_score'] ?? $mark->endterm_score;
            
            if ($midterm !== null && $endterm !== null) {
                $average = ($midterm + $endterm) / 2;
                $validated['grade'] = $this->marksService->calculateGrade($average);
            }
        }

        $mark->update($validated);

        return redirect()->route('admin.marks.index')->with('success', 'Mark updated successfully.');
    }

    public function destroy($id)
    {
        $mark = Mark::findOrFail($id);
        
        if (!Gate::allows('delete', $mark)) {
            abort(403);
        }

        $mark->delete();

        return redirect()->route('admin.marks.index')->with('success', 'Mark deleted successfully.');
    }

    public function getStudentAverages(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'term_id' => 'required|exists:terms,id'
        ]);

        $averages = $this->marksService->calculateStudentAverages(
            $validated['student_id'],
            $validated['term_id']
        );

        return response()->json($averages);
    }

    // NEW METHODS ADDED BELOW

    public function studentSubjectsIndex(Request $request)
    {
        $query = StudentSubject::with(['student.user', 'subject', 'class', 'academicYear']);
        
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        
        $studentSubjects = $query->latest()->paginate(50);
        
        $academicYears = AcademicYear::all();
        $classes = ClassModel::all();
        $subjects = Subject::all();
        
        return view('admin.student-subjects.index', compact('studentSubjects', 'academicYears', 'classes', 'subjects'));
    }

    public function studentSubjectsCreate()
    {
        $academicYears = AcademicYear::all();
        return view('admin.student-subjects.create', compact('academicYears'));
    }

    public function studentSubjectsStore(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'is_elective' => 'boolean'
        ]);
        
        $assignments = [];
        foreach ($validated['student_ids'] as $studentId) {
            $assignments[] = [
                'student_id' => $studentId,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'is_elective' => $request->boolean('is_elective'),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        try {
            DB::table('student_subjects')->upsert(
                $assignments,
                ['student_id', 'subject_id', 'academic_year_id'],
                ['class_id', 'is_elective', 'updated_at']
            );
            
            return redirect()->route('admin.student-subjects.index')
                            ->with('success', 'Student subject assignments created successfully.');
        } catch (\Exception $e) {
            Log::error('Student subject assignment failed: ' . $e->getMessage());
            return redirect()->back()
                            ->withErrors(['error' => 'Failed to create assignments. Please try again.'])
                            ->withInput();
        }
    }

    public function studentSubjectsDestroy($id)
    {
        try {
            StudentSubject::findOrFail($id)->delete();
            return redirect()->route('admin.student-subjects.index')
                            ->with('success', 'Assignment removed successfully.');
        } catch (\Exception $e) {
            Log::error('Student subject removal failed: ' . $e->getMessage());
            return redirect()->back()
                            ->withErrors(['error' => 'Failed to remove assignment. Please try again.']);
        }
    }

    // AJAX methods for dynamic loading
    public function getClassesByAcademicYear($academicYearId)
    {
        // Validate academic year exists
        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }
        
        try {
            $classes = ClassModel::whereHas('classSubjects', function($query) use ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            })->get();
            
            return response()->json($classes);
        } catch (\Exception $e) {
            Log::error('Failed to load classes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load classes'], 500);
        }
    }

public function getStudentsByClass($classId, $academicYearId)
{
    if (!ClassModel::find($classId)) {
        return response()->json(['error' => 'Invalid class'], 404);
    }

    if (!AcademicYear::find($academicYearId)) {
        return response()->json(['error' => 'Invalid academic year'], 404);
    }

    try {
        $students = Student::whereHas('classHistory', function ($query) use ($classId, $academicYearId) {
            $query->where('class_id', $classId)
                  ->where('academic_year_id', $academicYearId)
                  ->where('status', 'active')
                  ->whereNull('exited_at');
        })
        ->with('user')
        ->get()
        ->map(function ($student) {
            return [
                'id' => $student->id,
                'name' => $student->user->name ?? 'Unknown Student',
                'admission_no' => $student->admission_no ?? 'N/A',
            ];
        });

        return response()->json($students);
    } catch (\Exception $e) {
        Log::error('Failed to load students', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'class_id' => $classId,
            'academic_year_id' => $academicYearId,
        ]);

        return response()->json(['error' => $e->getMessage()], 500);
    }
}



    public function getSubjectsByClass($classId, $academicYearId)
    {
        // Validate class and academic year exist
        if (!ClassModel::find($classId)) {
            return response()->json(['error' => 'Invalid class'], 404);
        }
        
        if (!AcademicYear::find($academicYearId)) {
            return response()->json(['error' => 'Invalid academic year'], 404);
        }
        
        try {
            $subjects = Subject::whereHas('classSubjects', function($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                      ->where('academic_year_id', $academicYearId);
            })->get();
            
            return response()->json($subjects);
        } catch (\Exception $e) {
            Log::error('Failed to load subjects: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load subjects'], 500);
        }
    }
}
