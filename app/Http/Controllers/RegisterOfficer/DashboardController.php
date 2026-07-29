<?php

namespace App\Http\Controllers\RegisterOfficer;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\Event;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $date = Carbon::today();
        $academicYear = AcademicYear::current();

        $classes = ClassModel::with('classTeacher.user')
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->whereNotNull('class_teacher_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $holiday = Event::attendanceHolidayForDate($date, null, $academicYear?->id);

        $recordedClassIds = collect();
        if (! $holiday) {
            $recordedClassIds = Attendance::whereDate('attendance_date', $date->toDateString())
                ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
                ->pluck('class_id')
                ->unique()
                ->values();
        }

        $stats = [
            'classes' => $classes->count(),
            'recorded' => $holiday ? 0 : $recordedClassIds->count(),
            'missing' => $holiday ? 0 : max($classes->count() - $recordedClassIds->count(), 0),
            'holiday' => (bool) $holiday,
            'holiday_title' => $holiday['title'] ?? null,
            'events' => Event::count(),
            'upcoming_events' => Event::where('start_datetime', '>=', now())->count(),
        ];

        $upcomingEvents = Event::query()
            ->where('start_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->limit(5)
            ->get();

        return view('register-officer.dashboard', compact('date', 'academicYear', 'stats', 'upcomingEvents'));
    }
}
