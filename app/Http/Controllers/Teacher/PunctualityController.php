<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Punctuality;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PunctualityController extends Controller
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

        $selectedClassId = $request->input('class_id');
        $selectedDate = $request->input('record_date', now()->toDateString());

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

            if ($selectedClassId) {
                $class = ClassModel::where('id', $selectedClassId)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->where('class_teacher_id', $teacher->id)
                    ->firstOrFail();

                $students = Student::with('user')
                    ->where('current_class_id', $class->id)
                    ->orderBy('admission_no')
                    ->get();

                $existingStatuses = Punctuality::where('class_id', $class->id)
                    ->whereDate('record_date', $selectedDate)
                    ->pluck('status', 'student_id');

                $existingRemarks = Punctuality::where('class_id', $class->id)
                    ->whereDate('record_date', $selectedDate)
                    ->pluck('remarks', 'student_id');

                $students = $students->map(function ($student) use ($existingStatuses, $existingRemarks) {
                    $student->existing_status = $existingStatuses[$student->id] ?? Punctuality::STATUS_ON_TIME;
                    $student->existing_remarks = $existingRemarks[$student->id] ?? '';
                    return $student;
                });
            }
        }

        return view('teacher.punctuality.index', compact(
            'activeAcademicYear',
            'activeTerm',
            'classes',
            'students',
            'selectedClassId',
            'selectedDate'
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
            'record_date' => ['required', 'date'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'exists:students,id'],
            'students.*.status' => ['required', Rule::in(Punctuality::statuses())],
            'students.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $activeAcademicYear = AcademicYear::where('active', true)->firstOrFail();

        $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
            ->where('status', Term::STATUS_ACTIVE)
            ->first();

        if (! $activeTerm) {
            return back()->withErrors([
                'record_date' => 'No active term found. Punctuality cannot be recorded without an active term.',
            ])->withInput();
        }

        $class = ClassModel::where('id', $validated['class_id'])
            ->where('academic_year_id', $activeAcademicYear->id)
            ->where('class_teacher_id', $teacher->id)
            ->first();

        if (! $class) {
            abort(403, 'You are not the class teacher for this class.');
        }

        DB::transaction(function () use ($validated, $teacher, $activeAcademicYear, $activeTerm, $class) {
            foreach ($validated['students'] as $row) {
                $student = Student::where('id', $row['student_id'])
                    ->where('current_class_id', $class->id)
                    ->first();

                if (! $student) {
                    continue;
                }

                Punctuality::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'record_date' => $validated['record_date'],
                    ],
                    [
                        'class_id' => $class->id,
                        'teacher_id' => $teacher->id,
                        'academic_year_id' => $activeAcademicYear->id,
                        'term_id' => $activeTerm->id,
                        'status' => $row['status'],
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );
            }
        });

        return redirect()->route('teacher.punctuality.index', [
            'class_id' => $class->id,
            'record_date' => $validated['record_date'],
        ])->with('success', 'Punctuality records saved successfully.');
    }
}
