<?php

namespace App\Services\Timetable;

use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\Tt\Setting;
use Illuminate\Support\Collection;

class TeacherLoadService
{
    /** @return array<string, mixed> */
    public function summary(?int $academicYearId = null, bool $publishedOnly = true): array
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
                ->with(['subject:id,name,code', 'teachers:id'])
                ->whereIn('tt_setting_id', $settings->pluck('id'))
                ->get()
                ->groupBy('tt_setting_id');

        return [
            'academic_year' => $year?->year_name,
            'schedules' => $settings->map(fn (Setting $setting) => [
                'id' => (int) $setting->id,
                'type' => $setting->schedule_type,
                'label' => $setting->typeLabel(),
                'name' => $setting->name,
                'cycle_length' => (int) $setting->cycle_length,
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
                ->map(fn (Collection $rows) => [
                    'subject' => $rows->first()?->subject?->name ?? 'Unknown subject',
                    'code' => $rows->first()?->subject?->code,
                    'periods' => round((float) $rows->sum(fn ($lesson) => (float) $lesson->periods_per_week), 1),
                ])
                ->sortBy('subject')
                ->values();

            return [
                'type' => $setting->schedule_type,
                'label' => $setting->typeLabel(),
                'setting_id' => (int) $setting->id,
                'subjects' => $subjects->all(),
                'total' => round((float) $subjects->sum('periods'), 1),
            ];
        })->values();

        return [
            'teacher_id' => (int) $teacher->id,
            'teacher_name' => $teacher->user?->name ?? 'Teacher',
            'schedules' => $scheduleRows->all(),
            'grand_total' => round((float) $scheduleRows->sum('total'), 1),
        ];
    }
}
