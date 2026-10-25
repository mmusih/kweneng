<?php

namespace App\Services\Timetable;

use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use Illuminate\Support\Collection;

/** Filter a schedule without deleting the saved lessons or their placements. */
class ClassParticipation
{
    public static function apply(Collection $lessons, ?Setting $setting = null): Collection
    {
        return $lessons->map(fn (Lesson $lesson) => self::lesson($lesson, $setting))
            ->filter()->values();
    }

    public static function lesson(Lesson $lesson, ?Setting $setting = null): ?Lesson
    {
        $excluded = ($setting ?? $lesson->setting)?->excluded_class_ids ?? [];
        if ($excluded === []) {
            return $lesson;
        }

        $lesson->loadMissing(['classes', 'groups']);
        $copy = clone $lesson;
        $hadClasses = $lesson->classes->isNotEmpty() || $lesson->groups->isNotEmpty();
        $copy->setRelation('classes', $lesson->classes->whereNotIn('id', $excluded)->values());
        $copy->setRelation('groups', $lesson->groups->whereNotIn('class_id', $excluded)->values());

        return $hadClasses && $copy->classes->isEmpty() && $copy->groups->isEmpty() ? null : $copy;
    }

    public static function cards(Collection $cards): Collection
    {
        return $cards->map(function ($card) {
            $lesson = self::lesson($card->lesson);
            if (! $lesson) {
                return null;
            }
            $copy = clone $card;

            return $copy->setRelation('lesson', $lesson);
        })->filter()->values();
    }
}
