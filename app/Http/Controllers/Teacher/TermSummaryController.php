<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentTermSummary;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TermSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        $teacher = $user->teacher;

        $activeAcademicYear = AcademicYear::where('active', true)->first();
        $activeTerm = null;
        $classes = collect();
        $students = collect();
        $termTotalDays = null;

        $selectedClassId = $request->input('class_id');

        if ($activeAcademicYear) {
            $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Term::STATUS_ACTIVE)
                ->first();

            $classes = ClassModel::with('classTeacher.user')
                ->where('academic_year_id', $activeAcademicYear->id)
                ->where('class_teacher_id', $teacher->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            if ($selectedClassId && $activeTerm) {
                $class = ClassModel::where('id', $selectedClassId)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->where('class_teacher_id', $teacher->id)
                    ->firstOrFail();

                $students = Student::with('user')
                    ->where('current_class_id', $class->id)
                    ->orderBy('admission_no')
                    ->get();

                $existing = StudentTermSummary::where('class_id', $class->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->where('term_id', $activeTerm->id)
                    ->get()
                    ->keyBy('student_id');

                $firstSummary = $existing->first();
                $termTotalDays = $firstSummary?->attendance_total_days;

                $students = $students->map(function ($student) use ($existing) {
                    $summary = $existing->get($student->id);

                    $student->existing_attendance_days_present = $summary?->attendance_days_present;
                    $student->existing_punctuality = $summary?->punctuality;
                    $student->existing_behaviour = $summary?->behaviour;
                    $student->existing_summary_remarks = $summary?->remarks;

                    return $student;
                });
            }
        }

        return view('teacher.term-summary.index', compact(
            'activeAcademicYear',
            'activeTerm',
            'classes',
            'students',
            'selectedClassId',
            'termTotalDays'
        ));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        $teacher = $user->teacher;

        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'attendance_total_days' => ['required', 'integer', 'min:1'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'exists:students,id'],
            'students.*.attendance_days_present' => ['nullable', 'integer', 'min:0'],
            'students.*.punctuality' => ['nullable', Rule::in(StudentTermSummary::PUNCTUALITY_LABELS)],
            'students.*.behaviour' => ['nullable', Rule::in(StudentTermSummary::BEHAVIOUR_LABELS)],
            'students.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $activeAcademicYear = AcademicYear::where('active', true)->firstOrFail();

        $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
            ->where('status', Term::STATUS_ACTIVE)
            ->firstOrFail();

        $class = ClassModel::where('id', $validated['class_id'])
            ->where('academic_year_id', $activeAcademicYear->id)
            ->where('class_teacher_id', $teacher->id)
            ->first();

        if (! $class) {
            abort(403, 'You are not the class teacher for this class.');
        }

        $totalDays = (int) $validated['attendance_total_days'];

        foreach ($validated['students'] as $index => $row) {
            $presentDays = $row['attendance_days_present'] ?? null;

            if ($presentDays !== null && (int) $presentDays > $totalDays) {
                return back()
                    ->withErrors([
                        "students.$index.attendance_days_present" => 'Days present cannot be more than the total number of days for the term.',
                    ])
                    ->withInput();
            }
        }

        DB::transaction(function () use ($validated, $activeAcademicYear, $activeTerm, $class, $totalDays) {
            foreach ($validated['students'] as $row) {
                $student = Student::where('id', $row['student_id'])
                    ->where('current_class_id', $class->id)
                    ->first();

                if (! $student) {
                    continue;
                }

                StudentTermSummary::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'term_id' => $activeTerm->id,
                    ],
                    [
                        'class_id' => $class->id,
                        'academic_year_id' => $activeAcademicYear->id,
                        'attendance_total_days' => $totalDays,
                        'attendance_days_present' => $row['attendance_days_present'] ?? null,
                        'punctuality' => $row['punctuality'] ?? null,
                        'behaviour' => $row['behaviour'] ?? null,
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('teacher.term-summary.index', [
            'class_id' => $class->id,
        ])->with('success', 'Term summary saved successfully.');
    }
}
