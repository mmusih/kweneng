<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Event;
use App\Models\ParentAbsenceNotice;
use App\Models\Student;
use App\Models\Term;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $this->currentTeacher($request);

        $activeAcademicYear = AcademicYear::where('active', true)->first();
        $activeTerm = $activeAcademicYear
            ? Term::where('academic_year_id', $activeAcademicYear->id)->where('status', Term::STATUS_ACTIVE)->first()
            : null;

        $classes = $activeAcademicYear
            ? $this->teacherClasses($teacher->id, $activeAcademicYear->id)
            : collect();

        $selectedClassId = $request->input('class_id');
        $selectedDate = $this->safeDate($request->input('attendance_date', now()->toDateString()))->toDateString();
        $selectedDateCarbon = Carbon::parse($selectedDate)->startOfDay();

        $selectedClass = null;
        $students = collect();
        $holiday = null;
        $dateWarning = null;

        if ($activeAcademicYear && $activeTerm && $selectedClassId) {
            $selectedClass = $this->authorizedClass($teacher->id, $activeAcademicYear->id, (int) $selectedClassId);
            $holiday = Event::attendanceHolidayForDate($selectedDateCarbon, $selectedClass->id, $selectedClass->academic_year_id);
            $dateWarning = $this->termDateWarning($selectedDateCarbon, $activeTerm);

            $existingAttendance = Attendance::where('class_id', $selectedClass->id)
                ->whereDate('attendance_date', $selectedDate)
                ->get()
                ->keyBy('student_id');

            $parentNotices = $this->parentAbsenceNoticesForDate($selectedClass, $selectedDateCarbon)
                ->keyBy('student_id');

            $students = Student::with('user')
                ->where('current_class_id', $selectedClass->id)
                ->orderBy('admission_no')
                ->get()
                ->map(function (Student $student) use ($existingAttendance, $parentNotices) {
                    $attendance = $existingAttendance->get($student->id);
                    $notice = $parentNotices->get($student->id);

                    $student->existing_status = $attendance?->status ?? Attendance::STATUS_PRESENT;
                    $student->existing_remarks = $attendance?->remarks ?? '';
                    $student->existing_attendance = $attendance;
                    $student->parent_absence_notice = $notice;

                    return $student;
                });
        }

        return view('teacher.attendance.index', compact(
            'activeAcademicYear',
            'activeTerm',
            'classes',
            'students',
            'selectedClassId',
            'selectedDate',
            'selectedClass',
            'holiday',
            'dateWarning'
        ));
    }

    public function store(Request $request)
    {
        $teacher = $this->currentTeacher($request);

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

        $class = $this->authorizedClass($teacher->id, $activeAcademicYear->id, (int) $validated['class_id']);
        $attendanceDate = Carbon::parse($validated['attendance_date'])->startOfDay();
        $holiday = Event::attendanceHolidayForDate($attendanceDate, $class->id, $class->academic_year_id);

        if ($holiday) {
            return back()->withErrors([
                'attendance_date' => 'This date is marked as a holiday on the calendar: ' . $holiday['title'] . '. Attendance cannot be saved for a holiday.',
            ])->withInput();
        }

        $dateWarning = $this->termDateWarning($attendanceDate, $activeTerm);
        if ($dateWarning) {
            return back()->withErrors(['attendance_date' => $dateWarning])->withInput();
        }

        $notices = $this->parentAbsenceNoticesForDate($class, $attendanceDate)->keyBy('student_id');
        $hasParentNoticeColumn = Schema::hasColumn('attendances', 'parent_absence_notice_id');
        $hasRecordedFromParentColumn = Schema::hasColumn('attendances', 'recorded_from_parent_notice');

        DB::transaction(function () use (
            $validated,
            $teacher,
            $activeAcademicYear,
            $activeTerm,
            $class,
            $attendanceDate,
            $notices,
            $hasParentNoticeColumn,
            $hasRecordedFromParentColumn,
            $request
        ) {
            foreach ($validated['students'] as $row) {
                $student = Student::where('id', $row['student_id'])
                    ->where('current_class_id', $class->id)
                    ->first();

                if (! $student) {
                    continue;
                }

                $notice = $notices->get($student->id);
                $payload = [
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'term_id' => $activeTerm->id,
                    'status' => $row['status'],
                    'remarks' => $row['remarks'] ?? null,
                ];

                if ($hasParentNoticeColumn) {
                    $payload['parent_absence_notice_id'] = $notice?->id;
                }

                if ($hasRecordedFromParentColumn) {
                    $payload['recorded_from_parent_notice'] = (bool) $notice;
                }

                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'attendance_date' => $attendanceDate->toDateString(),
                    ],
                    $payload
                );

                if ($notice) {
                    if (in_array($row['status'], [Attendance::STATUS_ABSENT, Attendance::STATUS_EXCUSED], true)) {
                        $notice->markResolved($request->user());
                    } else {
                        $notice->markSeen($request->user());
                    }
                }
            }
        });

        return redirect()->route('teacher.attendance.index', [
            'class_id' => $class->id,
            'attendance_date' => $attendanceDate->toDateString(),
        ])->with('success', 'Attendance saved successfully. Parent absence notices were factored into the official register.');
    }

    public function summary(Request $request)
    {
        return view('teacher.attendance.summary', $this->summaryData($request));
    }

    public function print(Request $request)
    {
        return view('teacher.attendance.print', $this->summaryData($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->summaryData($request);

        abort_unless($data['selectedClass'], 404, 'Please select a class before downloading the attendance PDF.');

        $fileName = Str::slug($data['selectedClass']->name) . '-attendance-' . $data['fromDate']->format('Ymd') . '-' . $data['toDate']->format('Ymd') . '.pdf';

        return Pdf::loadView('teacher.attendance.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }

    public function csv(Request $request)
    {
        $data = $this->summaryData($request);

        abort_unless($data['selectedClass'], 404, 'Please select a class before downloading the attendance CSV.');

        $fileName = Str::slug($data['selectedClass']->name) . '-attendance-' . $data['fromDate']->format('Ymd') . '-' . $data['toDate']->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Class',
                $data['selectedClass']->name,
                'From',
                $data['fromDate']->toDateString(),
                'To',
                $data['toDate']->toDateString(),
            ]);
            fputcsv($handle, []);

            $header = ['Admission No', 'Student Name'];
            foreach ($data['dates'] as $date) {
                $header[] = $date->format('d M');
            }
            $header = array_merge($header, ['Present', 'Absent', 'Late', 'Excused', 'Recorded Days', 'Attendance %']);
            fputcsv($handle, $header);

            foreach ($data['studentRows'] as $row) {
                $line = [
                    $row['student']->admission_no,
                    $row['student']->user->name ?? 'Unnamed Student',
                ];

                foreach ($data['dates'] as $date) {
                    $line[] = $row['records'][$date->toDateString()]['code'] ?? '';
                }

                $line[] = $row['counts'][Attendance::STATUS_PRESENT];
                $line[] = $row['counts'][Attendance::STATUS_ABSENT];
                $line[] = $row['counts'][Attendance::STATUS_LATE];
                $line[] = $row['counts'][Attendance::STATUS_EXCUSED];
                $line[] = $row['recorded_days'];
                $line[] = $row['attendance_percentage'] !== null ? number_format($row['attendance_percentage'], 2) . '%' : '';

                fputcsv($handle, $line);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Legend', 'P = Present', 'A = Absent', 'L = Late', 'E = Excused', 'H = Holiday', 'Blank = Not recorded']);
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function summaryData(Request $request): array
    {
        $teacher = $this->currentTeacher($request);

        $activeAcademicYear = AcademicYear::where('active', true)->first();
        $activeTerm = $activeAcademicYear
            ? Term::where('academic_year_id', $activeAcademicYear->id)->where('status', Term::STATUS_ACTIVE)->first()
            : null;

        $classes = $activeAcademicYear
            ? $this->teacherClasses($teacher->id, $activeAcademicYear->id)
            : collect();

        $selectedClassId = $request->input('class_id', $classes->first()?->id);
        $selectedClass = null;

        if ($activeAcademicYear && $selectedClassId) {
            $selectedClass = $this->authorizedClass($teacher->id, $activeAcademicYear->id, (int) $selectedClassId);
        }

        $defaultFrom = $activeTerm?->start_date?->toDateString() ?? now()->startOfMonth()->toDateString();
        $defaultTo = $activeTerm?->end_date?->toDateString() ?? now()->endOfMonth()->toDateString();

        $fromDate = $this->safeDate($request->input('from_date', $defaultFrom));
        $toDate = $this->safeDate($request->input('to_date', $defaultTo));

        if ($toDate->lt($fromDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $includeWeekends = $request->boolean('include_weekends', false);

        $holidayDates = $selectedClass
            ? Event::attendanceHolidayDatesBetween($fromDate, $toDate, $selectedClass->id, $selectedClass->academic_year_id)
            : collect();

        $dates = $this->registerDates($fromDate, $toDate, $includeWeekends, $holidayDates);
        $dateKeys = $dates->map(fn (Carbon $date) => $date->toDateString())->values();

        $students = collect();
        $studentRows = collect();
        $dailyRows = collect();
        $totals = $this->emptyStatusCounts();
        $classAttendancePercentage = null;
        $recordedTeachingDates = collect();
        $unmarkedTeachingDays = 0;

        if ($selectedClass) {
            $students = Student::with('user')
                ->where('current_class_id', $selectedClass->id)
                ->orderBy('admission_no')
                ->get();

            $attendances = Attendance::with(['student.user', 'parentAbsenceNotice'])
                ->where('class_id', $selectedClass->id)
                ->whereBetween('attendance_date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->get();

            $recordedTeachingDates = $attendances
                ->map(fn (Attendance $attendance) => $attendance->attendance_date->toDateString())
                ->unique()
                ->filter(fn (string $date) => $dateKeys->contains($date) && ! $holidayDates->has($date))
                ->values();

            $attendanceByStudentDate = $attendances
                ->groupBy('student_id')
                ->map(fn ($records) => $records->keyBy(fn (Attendance $attendance) => $attendance->attendance_date->toDateString()));

            $studentRows = $students->map(function (Student $student) use ($attendanceByStudentDate, $recordedTeachingDates, $dates, $holidayDates) {
                $studentRecords = $attendanceByStudentDate->get($student->id, collect());
                $counts = $this->emptyStatusCounts();
                $records = [];
                $recorded = 0;

                foreach ($dates as $date) {
                    $dateKey = $date->toDateString();
                    $holiday = $holidayDates->get($dateKey);

                    if ($holiday) {
                        $records[$dateKey] = [
                            'code' => 'H',
                            'label' => 'Holiday: ' . $holiday['title'],
                            'status' => 'holiday',
                            'remarks' => $holiday['title'],
                        ];
                        continue;
                    }

                    $attendance = $studentRecords->get($dateKey);

                    if (! $attendance) {
                        $records[$dateKey] = [
                            'code' => '',
                            'label' => 'Not recorded',
                            'status' => null,
                            'remarks' => null,
                        ];
                        continue;
                    }

                    $records[$dateKey] = [
                        'code' => Attendance::statusCode($attendance->status),
                        'label' => Attendance::statusLabel($attendance->status),
                        'status' => $attendance->status,
                        'remarks' => $attendance->remarks,
                    ];
                }

                foreach ($recordedTeachingDates as $dateKey) {
                    $attendance = $studentRecords->get($dateKey);
                    if (! $attendance) {
                        continue;
                    }

                    if (array_key_exists($attendance->status, $counts)) {
                        $counts[$attendance->status]++;
                    }

                    $recorded++;
                }

                $presentForPercentage = $counts[Attendance::STATUS_PRESENT] + $counts[Attendance::STATUS_LATE];
                $attendancePercentage = $recorded > 0 ? round(($presentForPercentage / $recorded) * 100, 2) : null;

                return [
                    'student' => $student,
                    'records' => $records,
                    'counts' => $counts,
                    'recorded_days' => $recorded,
                    'attendance_percentage' => $attendancePercentage,
                ];
            });

            foreach ($studentRows as $row) {
                foreach (Attendance::attendanceCountStatuses() as $status) {
                    $totals[$status] += $row['counts'][$status];
                }
            }

            $dailyRows = $this->dailyRows($dates, $holidayDates, $attendances ?? collect());

            $totalRecorded = array_sum($totals);
            $classPresent = $totals[Attendance::STATUS_PRESENT] + $totals[Attendance::STATUS_LATE];
            $classAttendancePercentage = $totalRecorded > 0 ? round(($classPresent / $totalRecorded) * 100, 2) : null;

            $teachingDays = $dateKeys->filter(fn (string $date) => ! $holidayDates->has($date))->count();
            $unmarkedTeachingDays = max(0, $teachingDays - $recordedTeachingDates->count());
        }

        return [
            'activeAcademicYear' => $activeAcademicYear,
            'activeTerm' => $activeTerm,
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'includeWeekends' => $includeWeekends,
            'holidayDates' => $holidayDates,
            'dates' => $dates,
            'students' => $students,
            'studentRows' => $studentRows,
            'dailyRows' => $dailyRows,
            'totals' => $totals,
            'recordedTeachingDates' => $recordedTeachingDates,
            'unmarkedTeachingDays' => $unmarkedTeachingDays,
            'classAttendancePercentage' => $classAttendancePercentage,
        ];
    }

    private function dailyRows(Collection $dates, Collection $holidayDates, Collection $attendances): Collection
    {
        $attendanceByDate = $attendances->groupBy(fn (Attendance $attendance) => $attendance->attendance_date->toDateString());

        return $dates->map(function (Carbon $date) use ($holidayDates, $attendanceByDate) {
            $dateKey = $date->toDateString();
            $holiday = $holidayDates->get($dateKey);
            $counts = $this->emptyStatusCounts();
            $records = $attendanceByDate->get($dateKey, collect());

            foreach ($records as $attendance) {
                if (array_key_exists($attendance->status, $counts)) {
                    $counts[$attendance->status]++;
                }
            }

            return [
                'date' => $date,
                'is_holiday' => (bool) $holiday,
                'holiday_title' => $holiday['title'] ?? null,
                'recorded_count' => $records->count(),
                'counts' => $counts,
            ];
        });
    }

    private function registerDates(CarbonInterface $fromDate, CarbonInterface $toDate, bool $includeWeekends, Collection $holidayDates): Collection
    {
        $dates = collect();

        foreach (CarbonPeriod::create($fromDate->copy()->startOfDay(), $toDate->copy()->startOfDay()) as $date) {
            $isWeekend = $date->isWeekend();
            $isHoliday = $holidayDates->has($date->toDateString());

            if (! $includeWeekends && $isWeekend && ! $isHoliday) {
                continue;
            }

            $dates->push($date->copy()->startOfDay());
        }

        return $dates->values();
    }

    private function emptyStatusCounts(): array
    {
        return [
            Attendance::STATUS_PRESENT => 0,
            Attendance::STATUS_ABSENT => 0,
            Attendance::STATUS_LATE => 0,
            Attendance::STATUS_EXCUSED => 0,
        ];
    }

    private function parentAbsenceNoticesForDate(ClassModel $class, CarbonInterface $date): Collection
    {
        return ParentAbsenceNotice::with(['parent.user', 'student.user'])
            ->whereHas('student', fn ($query) => $query->where('current_class_id', $class->id))
            ->whereDate('absence_date', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('expected_return_date')
                    ->orWhereDate('expected_return_date', '>=', $date->toDateString());
            })
            ->whereIn('status', ['pending', 'seen'])
            ->latest()
            ->get()
            ->unique('student_id')
            ->values();
    }

    private function termDateWarning(CarbonInterface $date, Term $term): ?string
    {
        if ($term->start_date && $date->lt($term->start_date->copy()->startOfDay())) {
            return 'The selected date is before the active term starts. Please select a date within ' . $term->name . '.';
        }

        if ($term->end_date && $date->gt($term->end_date->copy()->startOfDay())) {
            return 'The selected date is after the active term ends. Please select a date within ' . $term->name . '.';
        }

        return null;
    }

    private function teacherClasses(int $teacherId, int $academicYearId): Collection
    {
        return ClassModel::with('academicYear')
            ->where('academic_year_id', $academicYearId)
            ->where('class_teacher_id', $teacherId)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    private function authorizedClass(int $teacherId, int $academicYearId, int $classId): ClassModel
    {
        return ClassModel::where('id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_teacher_id', $teacherId)
            ->firstOrFail();
    }

    private function currentTeacher(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->teacher) {
            abort(403, 'Unauthorized action.');
        }

        return $user->teacher;
    }

    private function safeDate(?string $date): Carbon
    {
        try {
            return Carbon::parse($date ?: now()->toDateString())->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }
}
