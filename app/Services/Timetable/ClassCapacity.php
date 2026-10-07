<?php

namespace App\Services\Timetable;

use App\Models\Tt\Setting;

class ClassCapacity
{
    public function limit(Setting $setting): int
    {
        return (int) $setting->cycle_length * $setting->periods()->count();
    }

    /** Whole-class lessons plus the busiest group in each independent division. */
    public function usage(Setting $setting): array
    {
        $loads = [];
        foreach ($setting->lessons()->with(['classes', 'groups'])->get() as $lesson) {
            $periods = $lesson->cardsRequired() * $lesson->periods_per_card;
            foreach ($lesson->classes as $class) {
                $loads[$class->id] ??= ['whole' => 0, 'divisions' => []];
                $groups = $lesson->groups->where('class_id', $class->id);
                if ($groups->isEmpty() || $groups->contains('entire_class', true)) {
                    $loads[$class->id]['whole'] += $periods;
                } else {
                    foreach ($groups as $group) {
                        $division = $group->tt_division_id ?? 'group-'.$group->id;
                        $loads[$class->id]['divisions'][$division][$group->id] =
                            ($loads[$class->id]['divisions'][$division][$group->id] ?? 0) + $periods;
                    }
                }
            }
        }

        return array_map(fn ($load) => $load['whole'] + array_sum(array_map('max', $load['divisions'])), $loads);
    }

}
