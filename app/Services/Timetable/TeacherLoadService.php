<?php

namespace App\Services\Timetable;

use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\Tt\Setting;
use App\Support\Timetable\Bitmask;
use Illuminate\Support\Collection;

class TeacherLoadService
{
    /** @return array<string, mixed> */
    public function summary(?int $academicYearId = null, bool $publishedOnly = true, array $excludedForms = []): array
    {
        $academicYearId ??= AcademicYear::current()?->id;
        $year = $academicYearId ? AcademicYear::find($academicYearId) : null;

        $settings = $academicYearId
            ? Setting::query()
                ->where('academic_year_id', $academicYearId)
                ->when($publishedOnly, fn ($query) => $query->where('is_active', true)->where('is_published', true))
                ->orderByRaw("CASE WHEN schedule_type = 'day' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->get()
                ->unique('schedule_type')
                ->values()
            : collect();

        $teachers = Teacher::query()
            ->with('user:id,name,status')
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->get()
            ->sortBy(fn (Teacher $teacher) => $teacher->user?->name)
            ->values();

        $lessons = $settings->isEmpty()
            ? collect()
            : \App\Models\Tt\Lesson::query()
                ->with(['subject:id,name,code', 'teachers:id', 'cards', 'classes:id,name,level', 'groups.class'])
                ->whereIn('tt_setting_id', $settings->pluck('id'))
                ->get();
        $lessons = FormExclusions::apply($lessons, $excludedForms)->groupBy('tt_setting_id');

        return [
            'academic_year' => $year?->year_name,
            'schedules' => $settings->map(fn (Setting $setting) => [
                'id' => (int) $setting->id,
                'type' => $setting->schedule_type,
                'label' => $setting->typeLabel(),
                'name' => $setting->name,
                'cycle_length' => (int) $setting->cycle_length,
                'published' => (bool) $setting->is_published,
                'revision' => (int) $setting->revision,
                'term_label' => $setting->term_label,
            ])->values()->all(),
            'teachers' => $teachers->map(fn (Teacher $teacher) => $this->teacherRow($teacher, $settings, $lessons))->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function forTeacher(Teacher $teacher, ?int $academicYearId = null): array
    {
        $summary = $this->summary($academicYearId, true);

        return collect($summary['teachers'])->firstWhere('teacher_id', (int) $teacher->id) ?? [
            'teacher_id' => (int) $teacher->id,
            'teacher_name' => $teacher->user?->name,
            'schedules' => [],
            'grand_total' => 0,
        ];
    }

    /** @param Collection<int, Setting> $settings */
    private function teacherRow(Teacher $teacher, Collection $settings, Collection $lessons): array
    {
        $scheduleRows = $settings->map(function (Setting $setting) use ($teacher, $lessons) {
            $teacherLessons = ($lessons->get($setting->id) ?? collect())
                ->filter(fn ($lesson) => $lesson->teachers->contains('id', $teacher->id));

            $subjects = $teacherLessons
                ->groupBy('subject_id')
                ->map(function (Collection $rows) use ($setting) {
                    $scheduledRows = $rows->filter(fn ($lesson) => count($this->scheduledSlots(collect([$lesson]), $setting)) > 0);

                    return [
                        'subject_id' => (int) $rows->first()->subject_id,
                        'subject' => $rows->first()?->subject?->name ?? 'Unknown subject',
                        'code' => $rows->first()?->subject?->code,
                        'periods' => $rows->sum(fn ($lesson) => $lesson->cardsRequired() * $lesson->periods_per_card),
                        'scheduled_periods' => count($this->scheduledSlots($rows, $setting)),
                        'classes' => $scheduledRows->flatMap(fn ($lesson) => $lesson->classes->pluck('name'))
                            ->merge($scheduledRows->flatMap(fn ($lesson) => $lesson->groups->map(fn ($group) => $group->class?->name)))
                            ->filter()->unique()->sort()->values()->all(),
                        'groups' => $scheduledRows->flatMap(fn ($lesson) => $lesson->groups->map(fn ($group) => ($group->class?->name ?? 'Class').' / '.$group->name))
                            ->unique()->sort()->values()->all(),
                        'days' => $this->dayCounts($rows, $setting),
                    ];
                })
                ->sortBy('subject')
                ->values();

            return [
                'type' => $setting->schedule_type,
                'label' => $setting->typeLabel(),
                'setting_id' => (int) $setting->id,
                'subjects' => $subjects->all(),
                'total' => round((float) $subjects->sum('periods'), 1),
                'scheduled_total' => count($this->scheduledSlots($teacherLessons, $setting)),
                'days' => $this->dayCounts($teacherLessons, $setting),
            ];
        })->values();

        return [
            'teacher_id' => (int) $teacher->id,
            'teacher_name' => $teacher->user?->name ?? 'Teacher',
            'schedules' => $scheduleRows->all(),
            'grand_total' => round((float) $scheduleRows->sum('total'), 1),
            'grand_scheduled' => (int) $scheduleRows->sum('scheduled_total'),
        ];
    }

    /** A double has two card rows. Joint classes and split groups share the same occupied slots. */
    private function scheduledSlots(Collection $lessons, Setting $setting): array
    {
        $slots = [];
        foreach ($lessons as $lesson) {
            foreach ($lesson->cards as $card) {
                if ((int) $card->period_number < 1 || ! str_contains((string) $card->weeks, '1') || ! str_contains((string) $card->terms, '1')) {
                    continue;
                }
                foreach ((new Bitmask((string) $card->days, max(1, (int) $setting->cycle_length)))->positions() as $day) {
                    $slots[$day.':'.$card->period_number] = $day;
                }
            }
        }

        return $slots;
    }

    private function dayCounts(Collection $lessons, Setting $setting): array
    {
        $slots = $this->scheduledSlots($lessons, $setting);
        $days = [];
        foreach (range(1, max(1, (int) $setting->cycle_length)) as $day) {
            $days[$day] = count(array_filter($slots, fn ($slotDay) => $slotDay === $day));
        }

        return $days;
    }
}
