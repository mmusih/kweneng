<?php

namespace App\Http\Controllers\RegisterOfficer;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegisterMonitorController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::current();
        $selectedDate = Carbon::parse($request->input('date', today()->toDateString()))->startOfDay();
        $selectedAcademicYearId = $request->input('academic_year_id', $activeYear?->id);

        $academicYears = AcademicYear::orderByDesc('year_name')->get();

        $classes = ClassModel::with(['classTeacher.user', 'academicYear'])
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->whereNotNull('class_teacher_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $attendanceRows = Attendance::query()
            ->whereDate('attendance_date', $selectedDate->toDateString())
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->get()
            ->groupBy('class_id');

        $lastRecordedDates = Attendance::query()
            ->selectRaw('class_id, MAX(attendance_date) as last_recorded_at')
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->groupBy('class_id')
            ->pluck('last_recorded_at', 'class_id');

        $rows = $classes->map(function (ClassModel $class) use ($selectedDate, $attendanceRows, $lastRecordedDates, $selectedAcademicYearId) {
            $holiday = Event::attendanceHolidayForDate($selectedDate, $class->id, $selectedAcademicYearId ? (int) $selectedAcademicYearId : null);
            $records = $attendanceRows->get($class->id, collect());
            $studentCount = $class->students()->count();

            return [
                'class' => $class,
                'teacher' => $class->classTeacher?->user?->name ?? 'No teacher name',
                'holiday' => $holiday,
                'student_count' => $studentCount,
                'recorded_count' => $records->count(),
                'complete' => $holiday ? true : ($studentCount > 0 && $records->count() >= $studentCount),
                'partial' => ! $holiday && $records->count() > 0 && $records->count() < $studentCount,
                'missing' => ! $holiday && $records->count() === 0,
                'present' => $records->where('status', Attendance::STATUS_PRESENT)->count(),
                'absent' => $records->where('status', Attendance::STATUS_ABSENT)->count(),
                'late' => $records->where('status', Attendance::STATUS_LATE)->count(),
                'excused' => $records->where('status', Attendance::STATUS_EXCUSED)->count(),
                'last_recorded_at' => $lastRecordedDates->get($class->id),
            ];
        });

        $summary = [
            'total' => $rows->count(),
            'complete' => $rows->where('complete', true)->where('holiday', null)->count(),
            'partial' => $rows->where('partial', true)->count(),
            'missing' => $rows->where('missing', true)->count(),
            'holidays' => $rows->filter(fn ($row) => ! empty($row['holiday']))->count(),
        ];

        return view('register-officer.registers.index', compact(
            'academicYears',
            'selectedDate',
            'selectedAcademicYearId',
            'rows',
            'summary'
        ));
    }

    public function csv(Request $request)
    {
        $activeYear = AcademicYear::current();
        $selectedDate = Carbon::parse($request->input('date', today()->toDateString()))->startOfDay();
        $selectedAcademicYearId = $request->input('academic_year_id', $activeYear?->id);

        $classes = ClassModel::with(['classTeacher.user'])
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->whereNotNull('class_teacher_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $attendanceRows = Attendance::whereDate('attendance_date', $selectedDate->toDateString())
            ->when($selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $selectedAcademicYearId))
            ->get()
            ->groupBy('class_id');

        $fileName = 'register-monitor-' . $selectedDate->toDateString() . '.csv';

        return response()->streamDownload(function () use ($classes, $attendanceRows, $selectedDate, $selectedAcademicYearId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Class', 'Class Teacher', 'Status', 'Students', 'Recorded', 'Present', 'Absent', 'Late', 'Excused', 'Holiday']);

            foreach ($classes as $class) {
                $records = $attendanceRows->get($class->id, collect());
                $holiday = Event::attendanceHolidayForDate($selectedDate, $class->id, $selectedAcademicYearId ? (int) $selectedAcademicYearId : null);
                $studentCount = $class->students()->count();
                $status = $holiday ? 'Holiday' : ($records->count() >= $studentCount && $studentCount > 0 ? 'Complete' : ($records->count() > 0 ? 'Partial' : 'Missing'));

                fputcsv($handle, [
                    $selectedDate->toDateString(),
                    $class->name,
                    $class->classTeacher?->user?->name,
                    $status,
                    $studentCount,
                    $records->count(),
                    $records->where('status', Attendance::STATUS_PRESENT)->count(),
                    $records->where('status', Attendance::STATUS_ABSENT)->count(),
                    $records->where('status', Attendance::STATUS_LATE)->count(),
                    $records->where('status', Attendance::STATUS_EXCUSED)->count(),
                    $holiday['title'] ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
