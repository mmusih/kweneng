<?php

namespace App\Services\Timetable;

use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrintScheduleService
{
    private array $lessonCache = [];
    private ?Collection $shorts = null;

    public function entities(Setting $setting, string $type, array $excludedForms = []): Collection
    {
        return match ($type) {
            'class' => ClassModel::where('academic_year_id', $setting->academic_year_id)->whereNotIn('level', $excludedForms)->orderBy('name')->get(),
            'teacher' => Teacher::with('user')->whereIn('id', DB::table('tt_lesson_teacher')->join('tt_lessons', 'tt_lessons.id', '=', 'tt_lesson_teacher.tt_lesson_id')->where('tt_lessons.tt_setting_id', $setting->id)->pluck('tt_lesson_teacher.teacher_id'))->get()->sortBy('user.name')->values(),
            'room' => $setting->rooms()->orderBy('name')->get(),
            default => collect(),
        };
    }

    public function bundle(Setting $setting, string $type, array $excludedForms = []): array
    {
        return $this->entities($setting, $type, $excludedForms)
            ->map(fn ($entity) => $this->build($setting, $type, $entity->id, $excludedForms))->all();
    }

    /** @return array<string, mixed> */
    public function build(Setting $setting, string $type, int $id, array $excludedForms = []): array
    {
        $entity = $this->entity($setting, $type, $id);
        $key = $setting->id.':'.implode(',', $excludedForms);
        $this->lessonCache[$key] ??= FormExclusions::apply($setting->lessons()->with(['subject', 'teachers.user', 'classes', 'groups.class', 'cards.room', 'setting'])->get(), $excludedForms);
        $lessons = $this->lessonCache[$key]
            ->filter(fn ($lesson) => match ($type) {
                'class' => $lesson->classes->contains('id', $id) || $lesson->groups->contains('class_id', $id),
                'teacher' => $lesson->teachers->contains('id', $id),
                'room' => $lesson->cards->contains('tt_room_id', $id),
            });
        $shorts = $this->shorts ??= DB::table('tt_subject_meta')->pluck('short_name', 'subject_id');
        $cells = [];

        foreach ($lessons as $lesson) {
            foreach ($lesson->cards as $card) {
                if ((int) $card->period_number < 1 || ! str_contains((string) $card->weeks, '1') || ! str_contains((string) $card->terms, '1')) {
                    continue;
                }
                if ($type === 'room' && (int) $card->tt_room_id !== $id) {
                    continue;
                }
                foreach ($card->daysMask()->positions() as $day) {
                    $cells[$day][(int) $card->period_number][] = [
                        'subject' => ($shorts[$lesson->subject_id] ?? null) ?: ($lesson->subject?->code ?: $lesson->subject?->name),
                        'teachers' => $lesson->teachers->pluck('user.name')->filter()->implode(', '),
                        'classes' => $lesson->classes->pluck('name')->merge($lesson->groups->pluck('class.name'))->filter()->unique()->implode(', '),
                        'teacher_codes' => $lesson->teachers->map(fn ($teacher) => 'T'.$teacher->id)->implode(', '),
                        'room_code' => $card->tt_room_id ? 'R'.$card->tt_room_id : '',
                        'groups' => $lesson->groups->pluck('name')->implode(', '),
                        'room' => $card->room?->name,
                    ];
                }
            }
        }

        return [
            'setting' => $setting,
            'title' => ucfirst($type).' timetable — '.$this->name($entity, $type),
            'entityName' => $this->name($entity, $type),
            'type' => $type,
            'periods' => $setting->periods()->get(),
            'breaks' => $setting->breaks()->orderBy('after_period')->get(),
            'excludedForms' => $excludedForms,
            'days' => range(1, max(1, (int) $setting->cycle_length)),
            'cells' => $cells,
            'teacherLegend' => $lessons->flatMap(fn ($lesson) => $lesson->teachers)->unique('id')->mapWithKeys(fn ($teacher) => ['T'.$teacher->id => $teacher->user?->name])->all(),
            'schoolName' => 'Kweneng International Secondary School',
            'logoPath' => public_path('images/logo.png'),
        ];
    }

    private function entity(Setting $setting, string $type, int $id): Model
    {
        $entity = match ($type) {
            'class' => ClassModel::query()->where('academic_year_id', $setting->academic_year_id)->find($id),
            'teacher' => Teacher::query()->whereIn('id', DB::table('tt_lesson_teacher')
                ->join('tt_lessons', 'tt_lessons.id', '=', 'tt_lesson_teacher.tt_lesson_id')
                ->where('tt_lessons.tt_setting_id', $setting->id)
                ->pluck('tt_lesson_teacher.teacher_id'))->find($id),
            'room' => Room::query()->where('tt_setting_id', $setting->id)->find($id),
            default => null,
        };

        if ($entity === null) {
            throw ValidationException::withMessages(['print' => 'That print target does not belong to this timetable.']);
        }

        return $entity;
    }

    private function name(Model $entity, string $type): string
    {
        return $type === 'teacher' ? ($entity->user?->name ?? 'Unknown teacher') : (string) $entity->name;
    }
}
