<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\MarksService;
use App\Models\Mark;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarksController extends Controller
{
    protected $marksService;

    public function __construct(MarksService $marksService)
    {
        $this->marksService = $marksService;
    }

    public function index()
    {
        $teacher = Auth::user()->teacher;
        $classes = $this->marksService->getTeacherClassesForMarks($teacher);
        
        return view('teacher.marks.index', compact('classes'));
    }

    public function showClassSubjects(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id'
        ]);

        $class = ClassModel::findOrFail($validated['class_id']);
        $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
        
        // Get subjects assigned to this teacher for this class and academic year
        $subjects = Subject::whereHas('teacherSubjects', function($query) use ($validated) {
            $query->where('teacher_id', Auth::user()->teacher->id)
                  ->where('class_id', $validated['class_id'])
                  ->where('academic_year_id', $validated['academic_year_id']);
        })->get();

        return response()->json([
            'subjects' => $subjects
        ]);
    }

    public function showStudents(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id'
        ]);

        $students = $this->marksService->getStudentsForMarksEntry(
            $validated['class_id'],
            $validated['subject_id'],
            $validated['academic_year_id']
        );

        // Get existing marks for these students
        $existingMarks = Mark::where('class_id', $validated['class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('term_id', $validated['term_id'])
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'students' => $students,
            'existing_marks' => $existingMarks
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'marks' => 'array',
            'marks.*' => 'array',
            'marks.*.midterm' => 'nullable|numeric|min:0|max:100',
            'marks.*.endterm' => 'nullable|numeric|min:0|max:100',
            'marks.*.remarks' => 'nullable|string|max:500'
        ]);

        $teacher = Auth::user()->teacher;
        
        // Validate permissions
        if (!$this->marksService->validateMarksEntry(
            $teacher,
            $validated['class_id'],
            $validated['subject_id'],
            $validated['academic_year_id'],
            $validated['term_id']
        )) {
            return redirect()->back()->withErrors([
                'marks' => 'You do not have permission to enter marks for this class/subject/term combination or the term is not active.'
            ]);
        }

        // Prepare marks data with validation
        $marksData = [];
        foreach ($validated['marks'] as $studentId => $scores) {
            // Validate that student exists and is enrolled
            $studentExists = \App\Models\Student::where('id', $studentId)->exists();
            if (!$studentExists) {
                return redirect()->back()->withErrors([
                    'marks' => "Invalid student ID: {$studentId}"
                ]);
            }
            
            $marksData[] = [
                'student_id' => $studentId,
                'subject_id' => $validated['subject_id'],
                'class_id' => $validated['class_id'],
                'teacher_id' => $teacher->id,
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'],
                'midterm_score' => $scores['midterm'] ?? null,
                'endterm_score' => $scores['endterm'] ?? null,
                'remarks' => $scores['remarks'] ?? null,
            ];
        }

        $result = $this->marksService->bulkUpsertMarks($marksData);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->withErrors([
            'marks' => $result['message']
        ]);
    }

    public function edit($id)
    {
        $mark = Mark::findOrFail($id);
        
        // Check if teacher owns this mark
        if ($mark->teacher_id !== Auth::user()->teacher->id) {
            abort(403);
        }

        return view('teacher.marks.edit', compact('mark'));
    }

    public function update(Request $request, $id)
    {
        $mark = Mark::findOrFail($id);
        
        // Check if teacher owns this mark
        if ($mark->teacher_id !== Auth::user()->teacher->id) {
            abort(403);
        }

        $validated = $request->validate([
            'midterm_score' => 'nullable|numeric|min:0|max:100',
            'endterm_score' => 'nullable|numeric|min:0|max:100',
            'remarks' => 'nullable|string|max:500'
        ]);

        // Recalculate grade when updating
        $midtermScore = $validated['midterm_score'] ?? null;
        $endtermScore = $validated['endterm_score'] ?? null;
        
        $average = null;
        if ($midtermScore !== null && $endtermScore !== null) {
            $average = ($midtermScore + $endtermScore) / 2;
        } elseif ($midtermScore !== null) {
            $average = $midtermScore;
        } elseif ($endtermScore !== null) {
            $average = $endtermScore;
        }
        
        $grade = $average !== null ? $this->marksService->calculateGrade($average) : null;

        $mark->update(array_merge($validated, ['grade' => $grade]));

        return redirect()->route('teacher.marks.index')->with('success', 'Mark updated successfully.');
    }

    public function loadTerms($academicYearId)
    {
        try {
            $terms = Term::where('academic_year_id', $academicYearId)
                        ->where('status', '!=', 'locked') // Only load active terms
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->get();
            
            return response()->json($terms);
        } catch (\Exception $e) {
            return response()->json([], 200); // Return empty array instead of error
        }
    }
}
