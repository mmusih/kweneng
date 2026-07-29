<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Term;
use App\Services\ActivityLogService;
use App\Services\AttendanceRegisterService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceRegisterService $registerService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['nullable', 'date'],
        ]);

        [$class, $term] = $this->context($request, (int) $validated['class_id']);
        $date = Carbon::parse($validated['date'] ?? now()->toDateString());
        $this->ensureDateIsInTerm($date, $term);

        $students = Student::with('user:id,name')
            ->where('current_class_id', $class->id)
            ->orderBy('admission_no')
            ->get();

        $existing = Attendance::query()
            ->where('class_id', $class->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        $notices = $this->registerService->applicableNotices($class, $date);

        return response()->json([
            'class' => ['id' => $class->id, 'name' => $class->name, 'level' => $class->level],
            'date' => $date->toDateString(),
            'term' => ['id' => $term->id, 'name' => $term->name],
            'statuses' => Attendance::statuses(),
            'students' => $students->map(function (Student $student) use ($existing, $notices) {
                $attendance = $existing->get($student->id);
                $notice = $notices->get($student->id);

                return [
                    'id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'name' => $student->user->name,
                    'status' => $attendance?->status ?? Attendance::STATUS_PRESENT,
                    'remarks' => $attendance?->remarks,
                    'saved' => (bool) $attendance,
                    'parent_absence_notice' => $notice ? [
                        'id' => $notice->id,
                        'reason' => $notice->reason,
                        'note' => $notice->note,
                        'absence_date' => $notice->absence_date?->toDateString(),
                        'expected_return_date' => $notice->expected_return_date?->toDateString(),
                        'status' => $notice->status,
                        'reported_by' => $notice->parent?->user?->name,
                    ] : null,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['required', 'date'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'integer', 'distinct', 'exists:students,id'],
            'students.*.status' => ['required', Rule::in(Attendance::statuses())],
            'students.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        [$class, $term] = $this->context($request, (int) $validated['class_id']);
        $date = Carbon::parse($validated['date']);
        $this->ensureDateIsInTerm($date, $term);

        $rosterIds = Student::where('current_class_id', $class->id)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $submittedIds = collect($validated['students'])->pluck('student_id')->map(fn ($id) => (int) $id)->sort()->values();

        if ($rosterIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages([
                'students' => 'Submit one attendance status for every learner currently in the class.',
            ]);
        }

        $saved = $this->registerService->save(
            class: $class,
            teacher: $request->user()->teacher,
            term: $term,
            date: $date,
            rows: $validated['students'],
            user: $request->user()
        );

        $this->activityLogService->log(
            'attendance.mobile_saved',
            'Teacher saved the class register from the mobile app',
            $class,
            ['date' => $date->toDateString(), 'records_count' => $saved->count()],
            $request
        );

        return response()->json([
            'message' => 'Attendance register saved successfully.',
            'date' => $date->toDateString(),
            'records_saved' => $saved->count(),
        ]);
    }

    private function context(Request $request, int $classId): array
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher, 404, 'Teacher profile not found.');

        $academicYear = AcademicYear::where('active', true)->firstOrFail();
        $term = Term::where('academic_year_id', $academicYear->id)
            ->where('status', Term::STATUS_ACTIVE)
            ->firstOrFail();
        $class = ClassModel::whereKey($classId)
            ->where('academic_year_id', $academicYear->id)
            ->where('class_teacher_id', $teacher->id)
            ->firstOrFail();

        return [$class, $term];
    }

    private function ensureDateIsInTerm(Carbon $date, Term $term): void
    {
        if (($term->start_date && $date->lt($term->start_date)) || ($term->end_date && $date->gt($term->end_date))) {
            throw ValidationException::withMessages([
                'date' => 'The register date must fall within the active term.',
            ]);
        }
    }
}
