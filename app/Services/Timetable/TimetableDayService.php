<?php

namespace App\Services\Timetable;

use App\Models\AcademicYear;
use App\Models\Tt\Setting;
use Carbon\Carbon;

class TimetableDayService
{
    public function selectedDayNumber(Setting $setting, Carbon|string|null $date = null): ?int
    {
        $date = Carbon::parse($date ?? now())->startOfDay();

        if ($date->isWeekend()) {
            return null;
        }

        $cycleLength = max(1, (int) $setting->cycle_length);
        $anchor = $setting->cycle_anchor_date?->copy()->startOfDay();
        $anchorDay = max(1, min($cycleLength, (int) ($setting->cycle_anchor_day ?: 1)));

        if ($anchor === null) {
            return (($date->dayOfWeekIso - 1) % $cycleLength) + 1;
        }

        $schoolDayOffset = $this->schoolDayOffset($anchor, $date);

        return $this->wrap($anchorDay + $schoolDayOffset, $cycleLength);
    }

    public function label(Carbon|string|null $date = null, ?Setting $setting = null): string
    {
        $date = Carbon::parse($date ?? now())->startOfDay();
        $setting ??= Setting::current(AcademicYear::current()?->id, Setting::TYPE_DAY);
        $day = $setting ? $this->selectedDayNumber($setting, $date) : null;

        return $date->format('l').($day === null ? '' : '|Day '.$day);
    }

    private function schoolDayOffset(Carbon $from, Carbon $to): int
    {
        if ($from->equalTo($to)) {
            return 0;
        }

        $direction = $from->lessThan($to) ? 1 : -1;
        $cursor = $from->copy();
        $offset = 0;

        while (! $cursor->equalTo($to)) {
            $cursor->addDays($direction);
            if (! $cursor->isWeekend()) {
                $offset += $direction;
            }
        }

        return $offset;
    }

    private function wrap(int $value, int $length): int
    {
        return (($value - 1) % $length + $length) % $length + 1;
    }
}
