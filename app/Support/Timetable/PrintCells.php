<?php

namespace App\Support\Timetable;

use Illuminate\Support\Collection;

class PrintCells
{
    /** Merge adjacent occupied cells, stopping at breaks and period gaps. */
    public static function row(Collection $periods, array $cells, Collection $breaks): array
    {
        $result = [];
        foreach ($periods as $period) {
            $number = (int) $period->period_number;
            $entries = $cells[$number] ?? [];
            $last = array_key_last($result);
            if ($entries && $last !== null && $result[$last]['entries'] === $entries
                && $result[$last]['end'] === $number - 1 && ! $breaks->contains('after_period', $number - 1)) {
                $result[$last]['span']++;
                $result[$last]['end'] = $number;
            } else {
                $result[] = ['start' => $number, 'end' => $number, 'span' => 1, 'entries' => $entries];
            }
        }

        return $result;
    }
}
