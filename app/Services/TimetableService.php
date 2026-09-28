<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Services\Timetable\CardsScheduleService;
use App\Services\Timetable\TimetableDayService;
use Carbon\Carbon;

class TimetableService
{
    public function __construct(
        private readonly CardsScheduleService $cardSchedules = new CardsScheduleService,
        private readonly TimetableDayService $dayService = new TimetableDayService,
    ) {}

    public function emptySchedule(Carbon|string|null $date = null): array
    {
        return $this->emptyPayload($date);
    }

    public function forTeacher(Teacher $teacher, Carbon|string|null $date = null, bool $includeSchedules = false): array
    {
        $schedule = $this->cardSchedules->forTeacher($teacher, $date);

        return $schedule
            ? ($includeSchedules ? $schedule : $this->compactPayload($schedule))
            : $this->emptyPayload($date);
    }

    public function forStudent(Student $student, Carbon|string|null $date = null, bool $includeSchedules = false): array
    {
        $schedule = $this->cardSchedules->forStudent($student, $date);

        return $schedule
            ? ($includeSchedules ? $schedule : $this->compactPayload($schedule))
            : $this->emptyPayload($date);
    }

    private function emptyPayload(Carbon|string|null $date): array
    {
        return [
            'template' => null,
            'date' => Carbon::parse($date ?? now())->toDateString(),
            'day_label' => $this->dayService->label($date),
            'selected_day_number' => null,
            'selected_day_name' => null,
            'days' => collect(),
            'schedules' => collect(),
        ];
    }

    private function compactPayload(array $schedule): array
    {
        return collect($schedule)->only([
            'template', 'date', 'selected_day_number', 'selected_day_name', 'days',
        ])->all();
    }
}
