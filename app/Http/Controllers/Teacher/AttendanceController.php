<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
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
        $selectedDate = $request->input('attendance_date', now()->toDateString());

        if ($activeAcademicYear) {
            $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Term::STATUS_ACTIVE)
                ->first();

            // ONLY classes where this teacher is the class teacher
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

                $existingAttendance = Attendance::where('class_id', $class->id)
                    ->whereDate('attendance_date', $selectedDate)
                    ->pluck('status', 'student_id');

                $existingRemarks = Attendance::where('class_id', $class->id)
                    ->whereDate('attendance_date', $selectedDate)
                    ->pluck('remarks', 'student_id');

                $students = $students->map(function ($student) use ($existingAttendance, $existingRemarks) {
                    $student->existing_status = $existingAttendance[$student->id] ?? Attendance::STATUS_PRESENT;
                    $student->existing_remarks = $existingRemarks[$student->id] ?? '';
                    return $student;
                });
            }
        }

        return view('teacher.attendance.index', compact(
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
            'attendance_date' => ['required', 'date'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'exists:students,id'],
            'students.*.status' => ['required', Rule::in(Attendance::statuses())],
            'students.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $activeAcademicYear = AcademicYear::where('active', true)->firstOrFail();

        $activeTerm = Term::where('academic_year_id', $activeAcademicYear->id)
            ->where('status', Term::STATUS_ACTIVE)
            ->first();

        if (! $activeTerm) {
            return back()->withErrors([
                'attendance_date' => 'No active term found. Attendance cannot be recorded without an active term.',
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

                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'attendance_date' => $validated['attendance_date'],
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

        return redirect()->route('teacher.attendance.index', [
            'class_id' => $class->id,
            'attendance_date' => $validated['attendance_date'],
        ])->with('success', 'Attendance saved successfully.');
    }
}