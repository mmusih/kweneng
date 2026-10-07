<?php

namespace App\Services\Timetable;

use Illuminate\Support\Collection;

class FormExclusions
{
    /** Keep shared lessons when at least one participating class remains. */
    public static function apply(Collection $lessons, array $excludedForms): Collection
    {
        if ($excludedForms === []) {
            return $lessons;
        }

        return $lessons->filter(function ($lesson) use ($excludedForms) {
            $hadClasses = $lesson->classes->isNotEmpty() || $lesson->groups->isNotEmpty();
            $lesson->setRelation('classes', $lesson->classes->reject(fn ($class) => in_array((int) $class->level, $excludedForms, true))->values());
            $lesson->setRelation('groups', $lesson->groups->reject(fn ($group) => $group->class && in_array((int) $group->class->level, $excludedForms, true))->values());

            return ! $hadClasses || $lesson->classes->isNotEmpty() || $lesson->groups->isNotEmpty();
        })->values();
    }
}
