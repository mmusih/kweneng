<?php

namespace App\Services\Timetable;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Lesson;
use App\Models\Tt\Period;
use App\Models\Tt\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Build the frozen public timetable payload from the grid's tt_* card model. */
class CardsScheduleService
{
    public function __construct(
        private readonly CardPlacementService $placement = new CardPlacementService,
        private readonly GroupMembershipResolver $memberships = new GroupMembershipResolver,
        private readonly TimetableDayService $dayService = new TimetableDayService,
    ) {}

    public function forTeacher(Teacher $teacher, Carbon|string|null $date = null): ?array
    {
        $settings = $this->published();

        if ($settings->isEmpty()) {
            return null;
        }

        return $this->combinedPayload($settings->map(function (Setting $setting) use ($teacher, $date) {
            $lessons = $this->lessons($setting)
                ->filter(fn (Lesson $lesson) => $lesson->teachers->contains('id', $teacher->id));

            return $this->payload($setting, $lessons, $date);
        }), $date);
    }

    public function forStudent(Student $student, Carbon|string|null $date = null): ?array
    {
        $settings = $this->published();

        if ($settings->isEmpty()) {
            return null;
        }

        return $this->combinedPayload($settings->map(function (Setting $setting) use ($student, $date) {
            $classId = (int) $student->current_class_id;
            $lessons = $this->lessons($setting)->filter(function (Lesson $lesson) use ($student, $classId) {
                if ($classId < 1 || ! $lesson->classes->contains('id', $classId)) {
                    return false;
                }

                $groups = $lesson->groups->where('class_id', $classId);

                if ($groups->isEmpty() || $groups->contains(fn ($group) => $group->entire_class)) {
                    return true;
                }

                return $groups->contains(fn ($group) => in_array(
                    (int) $student->id,
                    $this->memberships->studentIds($group),
                    true,
                ));
            });

            return $this->payload($setting, $lessons, $date);
        }), $date);
    }

    /** @return Collection<int, Setting> */
    private function published(): Collection
    {
        $yearId = \App\Models\AcademicYear::current()?->id;

        if (! $yearId) {
            return collect();
        }

        return Setting::query()
            ->where('academic_year_id', $yearId)
            ->where('is_active', true)
            ->where('is_published', true)
            ->orderByRaw("CASE WHEN schedule_type = 'day' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->get()
            ->unique('schedule_type')
            ->values();
    }

    /** @return Collection<int, Lesson> */
    private function lessons(Setting $setting): Collection
    {
        return $setting->lessons()->with([
            'setting',
            'subject',
            'teachers.user',
            'classes',
            'groups.lessons.teachers',
            'cards.room',
        ])->get();
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     */
    private function payload(Setting $setting, Collection $lessons, Carbon|string|null $date): array
    {
        $date = Carbon::parse($date ?? now())->startOfDay();
        $periods = $setting->periods()->get();
        $breaks = $setting->breaks()->get()->keyBy('after_period');
        $cycleLength = max(1, (int) $setting->cycle_length);
        $byDay = collect(range(1, $cycleLength))->mapWithKeys(fn (int $day) => [$day => collect()]);

        foreach ($lessons as $lesson) {
            foreach ($this->placement->unitsFor($lesson) as $unit) {
                if ($byDay->has($unit->dayNumber())) {
                    $byDay[$unit->dayNumber()]->push($unit);
                }
            }
        }

        $selected = $this->selectedDayNumber($setting, $date);

        return [
            'template' => [
                'id' => (int) $setting->id,
                'name' => $setting->name,
                'cycle_type' => $cycleLength === 5 ? 'weekly' : 'rotating',
                'cycle_length' => $cycleLength,
                'academic_year' => $setting->academicYear?->year_name,
                'schedule_type' => $setting->schedule_type,
                'schedule_label' => $setting->typeLabel(),
            ],
            'date' => $date->toDateString(),
            'day_label' => $this->dayService->label($date),
            'selected_day_number' => $selected,
            'selected_day_name' => $selected === null ? null : $this->dayName($selected, $cycleLength),
            'days' => collect(range(1, $cycleLength))->map(fn (int $day) => [
                'id' => $day,
                'day_number' => $day,
                'name' => $this->dayName($day, $cycleLength),
                'weekday' => $cycleLength === 5 ? $day : null,
                'blocks' => $this->blocks($periods, $breaks, $byDay[$day]),
            ])->values(),
        ];
    }

    /**
     * @param  Collection<int, Period>  $periods
     * @param  Collection<int, BreakPeriod>  $breaks
     * @param  Collection<int, Placement>  $placements
     * @return list<array<string, mixed>>
     */
    private function blocks(Collection $periods, Collection $breaks, Collection $placements): array
    {
        $blocks = [];
        $coveredThrough = 0;

        foreach ($periods as $period) {
            $number = (int) $period->period_number;

            if ($number > $coveredThrough) {
                $placement = $placements->first(fn (Placement $unit) => $unit->startPeriod() === $number);

                if ($placement === null) {
                    $blocks[] = $this->freeBlock($period);
                } else {
                    $coveredThrough = $number + $placement->span() - 1;
                    $blocks[] = $this->lessonBlock($period, $periods, $placement);
                }
            }

            if ($break = $breaks->get($number)) {
                $blocks[] = $this->breakBlock($break);
            }
        }

        return $blocks;
    }

    /** @return array<string, mixed> */
    private function lessonBlock(Period $start, Collection $periods, Placement $placement): array
    {
        $lesson = $placement->lesson;
        $endNumber = $placement->startPeriod() + $placement->span() - 1;
        $end = $periods->firstWhere('period_number', $endNumber) ?? $start;
        $teachers = $lesson->teachers->pluck('user.name')->filter()->implode(', ');
        $classes = $lesson->classes->pluck('name')->filter()->implode(', ');
        $groups = $lesson->groups->reject(fn ($group) => $group->entire_class)->pluck('name')->implode(', ');

        return [
            'kind' => 'lesson',
            'period_id' => (int) $start->id,
            'period_name' => $start->name,
            'end_period_name' => $end->name,
            'start_time' => $this->time($start->start_time),
            'end_time' => $this->time($end->end_time),
            'duration_minutes' => (int) Carbon::parse($start->start_time)->diffInMinutes(Carbon::parse($end->end_time)),
            'entry_id' => $placement->first()->id,
            'title' => $lesson->subject?->name ?? 'Lesson',
            'subject' => $lesson->subject?->name,
            'subject_code' => $lesson->subject?->code,
            'teacher' => $teachers !== '' ? $teachers : null,
            'class' => $classes !== '' ? $classes : null,
            'group' => $groups !== '' ? $groups : null,
            'room' => $placement->first()->room?->name,
            'notes' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function freeBlock(Period $period): array
    {
        return [
            'kind' => 'free',
            'period_id' => (int) $period->id,
            'period_name' => $period->name,
            'start_time' => $this->time($period->start_time),
            'end_time' => $this->time($period->end_time),
            'duration_minutes' => (int) Carbon::parse($period->start_time)->diffInMinutes(Carbon::parse($period->end_time)),
            'title' => 'Free period',
        ];
    }

    /** @return array<string, mixed> */
    private function breakBlock(BreakPeriod $break): array
    {
        return [
            'kind' => 'event',
            'period_id' => (int) $break->id,
            'period_name' => $break->name,
            'start_time' => $this->time($break->start_time),
            'end_time' => $this->time($break->end_time),
            'duration_minutes' => (int) Carbon::parse($break->start_time)->diffInMinutes(Carbon::parse($break->end_time)),
            'title' => $break->name,
            'event_type' => 'break',
        ];
    }

    private function selectedDayNumber(Setting $setting, Carbon $date): ?int
    {
        return $this->dayService->selectedDayNumber($setting, $date);
    }

    private function dayName(int $day, int $cycleLength): string
    {
        if ($cycleLength !== 5) {
            return 'Day '.$day;
        }

        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'][$day - 1];
    }

    private function time(mixed $value): string
    {
        return Carbon::parse($value)->format('H:i');
    }

    /**
     * Preserve the original top-level payload for older clients while exposing every
     * independently cycling schedule to updated web and mobile clients.
     *
     * @param  Collection<int, array<string, mixed>>  $schedules
     */
    private function combinedPayload(Collection $schedules, Carbon|string|null $date): array
    {
        $primary = $schedules->first() ?? [];

        return [
            ...$primary,
            'date' => Carbon::parse($date ?? now())->toDateString(),
            'day_label' => $this->dayService->label($date),
            'schedules' => $schedules->values(),
        ];
    }
}
