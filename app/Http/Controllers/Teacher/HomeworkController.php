<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Homework;
use App\Models\HomeworkMark;
use App\Models\Term;
use App\Services\ActivityLogService;
use App\Services\MarksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeworkController extends Controller
{
    public function __construct(
        protected MarksService $marksService,
        protected ActivityLogService $activityLogService
    ) {}

    public function index()
    {
        $teacher = Auth::user()->teacher;
        $classes = $this->marksService->getTeacherClassesForMarks($teacher);

        $homeworks = Homework::with(['class', 'subject', 'term'])
            ->where('teacher_id', $teacher->id)
            ->latest('assigned_date')
            ->latest('id')
            ->get();

        return view('teacher.homeworks.index', compact('classes', 'homeworks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'total_marks' => ['required', 'numeric', 'min:0.01'],
            'assigned_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:assigned_date'],
        ]);

        $teacher = Auth::user()->teacher;
        $term = Term::findOrFail($validated['term_id']);

        if (!$this->marksService->validateMarksEntry(
            $teacher,
            (int) $validated['class_id'],
            (int) $validated['subject_id'],
            (int) $validated['academic_year_id'],
            (int) $validated['term_id']
        )) {
            return back()->withErrors([
                'homework' => 'You do not have permission to create homework for this class, subject, and term combination.',
            ])->withInput();
        }

        if ($term->isLocked()) {
            return back()->withErrors([
                'homework' => 'This term is locked. Homework cannot be created.',
            ])->withInput();
        }

        $homework = Homework::create([
            'class_id' => $validated['class_id'],
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $teacher->id,
            'academic_year_id' => $validated['academic_year_id'],
            'term_id' => $validated['term_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'total_marks' => $validated['total_marks'],
            'assigned_date' => $validated['assigned_date'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $this->activityLogService->log(
            'homework.created',
            'Teacher created homework',
            $homework,
            [
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'title' => $homework->title,
                'total_marks' => $homework->total_marks,
            ],
            request()
        );

        return redirect()
            ->route('teacher.homeworks.index')
            ->with('success', 'Homework created successfully.');
    }

    public function marks(Homework $homework)
    {
        $teacher = Auth::user()->teacher;

        abort_unless($homework->teacher_id === $teacher->id, 403);

        $students = $this->marksService->getStudentsForMarksEntry(
            $homework->class_id,
            $homework->subject_id,
            $homework->academic_year_id
        );

        $existingMarks = HomeworkMark::where('homework_id', $homework->id)
            ->get()
            ->keyBy('student_id');

        $term = Term::findOrFail($homework->term_id);

        return view('teacher.homeworks.marks', compact('homework', 'students', 'existingMarks', 'term'));
    }

    public function storeMarks(Request $request, Homework $homework)
    {
        $teacher = Auth::user()->teacher;

        abort_unless($homework->teacher_id === $teacher->id, 403);

        $term = Term::findOrFail($homework->term_id);

        if ($term->isLocked()) {
            return back()->withErrors([
                'homework_marks' => 'This term is locked. Homework marks cannot be changed.',
            ]);
        }

        $validated = $request->validate([
            'marks' => ['required', 'array'],
            'marks.*.marks_obtained' => ['nullable', 'numeric', 'min:0', 'max:' . $homework->total_marks],
            'marks.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $homework) {
            foreach ($validated['marks'] as $studentId => $row) {
                $marksObtained = array_key_exists('marks_obtained', $row) && $row['marks_obtained'] !== null && $row['marks_obtained'] !== ''
                    ? (float) $row['marks_obtained']
                    : null;

                $percentage = null;
                $grade = null;

                if ($marksObtained !== null) {
                    $percentage = round(($marksObtained / (float) $homework->total_marks) * 100, 2);
                    $grade = $this->marksService->calculateGrade($percentage);
                }

                HomeworkMark::updateOrCreate(
                    [
                        'homework_id' => $homework->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'marks_obtained' => $marksObtained,
                        'percentage' => $percentage,
                        'grade' => $grade,
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );
            }
        });

        $this->activityLogService->log(
            'homework.marks_saved',
            'Teacher saved homework marks',
            $homework,
            [
                'homework_id' => $homework->id,
                'class_id' => $homework->class_id,
                'subject_id' => $homework->subject_id,
                'term_id' => $homework->term_id,
                'records_count' => count($validated['marks']),
            ],
            request()
        );

        return redirect()
            ->route('teacher.homeworks.index')
            ->with('success', 'Homework marks saved successfully.');
    }
}
