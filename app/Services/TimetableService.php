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

    public function currentActivity(Student $student): array
    {
        $now = now(config('app.timezone'));
        $schedule = $this->forStudent($student, $now, true);
        $blocks = collect($schedule['schedules'] ?? [])->flatMap(function ($item) use ($now) {
            if (($item['date'] ?? null) !== $now->toDateString() || $item['selected_day_number'] === null) {
                return [];
            }
            $day = collect($item['days'])->firstWhere('day_number', $item['selected_day_number']);
            return collect($day['blocks'] ?? [])->filter(fn ($block) =>
                $block['start_time'] <= $now->format('H:i') && $block['end_time'] > $now->format('H:i'))
                ->map(fn ($block) => $block + ['schedule_label' => $item['template']['schedule_label']]);
        })->values();
        return [
            'date' => $now->toDateString(),
            'checked_at' => $now->toIso8601String(),
            'blocks' => $blocks,
            'description' => $blocks->isNotEmpty()
                ? $blocks->map(fn ($block) => $block['title'].' · '.$block['start_time'].'–'.$block['end_time'].(($block['room'] ?? null) ? ' · '.$block['room'] : ''))->implode(' / ')
                : ($schedule['template'] ? 'No activity scheduled at this time' : 'Timetable not published yet'),
        ];
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
