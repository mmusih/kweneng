<?php

namespace App\Services\Timetable;

use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrintScheduleService
{
    /** @return array<string, mixed> */
    public function build(Setting $setting, string $type, int $id): array
    {
        $entity = $this->entity($setting, $type, $id);
        $lessons = $setting->lessons()->with(['subject', 'teachers.user', 'classes', 'groups', 'cards.room', 'setting'])->get()
            ->filter(fn ($lesson) => match ($type) {
                'class' => $lesson->classes->contains('id', $id),
                'teacher' => $lesson->teachers->contains('id', $id),
                'room' => $lesson->cards->contains('tt_room_id', $id),
            });
        $shorts = DB::table('tt_subject_meta')->pluck('short_name', 'subject_id');
        $cells = [];

        foreach ($lessons as $lesson) {
            foreach ($lesson->cards as $card) {
                if ($type === 'room' && (int) $card->tt_room_id !== $id) {
                    continue;
                }
                foreach ($card->daysMask()->positions() as $day) {
                    $cells[$day][(int) $card->period_number][] = [
                        'subject' => ($shorts[$lesson->subject_id] ?? null) ?: ($lesson->subject?->code ?: $lesson->subject?->name),
                        'teachers' => $lesson->teachers->pluck('user.name')->filter()->implode(', '),
                        'classes' => $lesson->classes->pluck('name')->implode(', '),
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
            'days' => range(1, max(1, (int) $setting->cycle_length)),
            'cells' => $cells,
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
