<?php

namespace App\Services\Timetable;

use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Card;
use App\Models\Tt\Period;
use App\Models\Tt\Setting;
use App\Support\Timetable\Bitmask;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The shape of the school day: how many periods, how many days in the cycle, and when
 * each period runs.
 *
 * `tt_periods.start_time` and `end_time` are NOT NULL, so they have to come from
 * somewhere — but nobody wants to type sixteen times by hand. They are computed from a
 * first-period start, a period length and the breaks, and stay editable afterwards.
 *
 * Growing the day is always safe. Shrinking it is not: the cards on the periods being
 * removed would go with them, so that is refused and the occupied periods named.
 */
class DayStructureService
{
    public const DEFAULT_BREAK_MINUTES = 20;

    /**
     * @param  list<array{after_period: int, minutes?: int, name?: string, short_name?: string}>  $breaks
     *
     * @throws DayStructureRefused
     */
    public function apply(
        Setting $setting,
        int $periodsPerDay,
        int $daysPerCycle,
        string $firstPeriodStart,
        int $periodMinutes,
        array $breaks = [],
    ): Collection {
        $this->refuseIfCardsWouldBeLost($setting, $periodsPerDay, $daysPerCycle);

        $breaks = $this->normaliseBreaks($breaks, $periodsPerDay);

        return DB::transaction(function () use (
            $setting, $periodsPerDay, $daysPerCycle, $firstPeriodStart, $periodMinutes, $breaks
        ) {
            $setting->update(['cycle_length' => $daysPerCycle]);

            Period::where('tt_setting_id', $setting->id)
                ->where('period_number', '>', $periodsPerDay)
                ->delete();

            $times = $this->timetableOfDay($firstPeriodStart, $periodMinutes, $periodsPerDay, $breaks);

            foreach ($times['periods'] as $number => [$start, $end]) {
                Period::updateOrCreate(
                    ['tt_setting_id' => $setting->id, 'period_number' => $number],
                    [
                        // Times are regenerated; names are not overwritten if an admin has
                        // renamed a period ("Registration") — only filled when absent.
                        'name' => Period::where('tt_setting_id', $setting->id)
                            ->where('period_number', $number)
                            ->value('name') ?: 'Period '.$number,
                        'short_name' => (string) $number,
                        'start_time' => $start,
                        'end_time' => $end,
                    ],
                );
            }

            // The setup screen owns the break list: it is rewritten from what was
            // submitted, so removing a break here removes it from the timetable.
            BreakPeriod::where('tt_setting_id', $setting->id)->delete();

            foreach ($times['breaks'] as $break) {
                BreakPeriod::create([
                    'tt_setting_id' => $setting->id,
                    'name' => $break['name'],
                    'short_name' => $break['short_name'],
                    'start_time' => $break['start_time'],
                    'end_time' => $break['end_time'],
                    'after_period' => $break['after_period'],
                ]);
            }

            $setting->unsetRelation('periods');
            $setting->unsetRelation('breaks');

            return $setting->periods()->get();
        });
    }

    /**
     * Clock times for a day, with each break pushing everything after it later.
     *
     * @param  list<array{after_period: int, minutes: int, name: string, short_name: string}>  $breaks
     * @return array{periods: array<int, array{0: string, 1: string}>, breaks: list<array<string, mixed>>}
     */
    public function timetableOfDay(
        string $firstPeriodStart,
        int $periodMinutes,
        int $periodsPerDay,
        array $breaks,
    ): array {
        $clock = CarbonImmutable::createFromFormat('H:i', $this->asHourMinute($firstPeriodStart));

        $periods = [];
        $placedBreaks = [];

        foreach (range(1, max(1, $periodsPerDay)) as $number) {
            $end = $clock->addMinutes($periodMinutes);

            $periods[$number] = [$clock->format('H:i:s'), $end->format('H:i:s')];
            $clock = $end;

            foreach ($breaks as $break) {
                if ($break['after_period'] !== $number) {
                    continue;
                }

                $breakEnd = $clock->addMinutes($break['minutes']);

                $placedBreaks[] = [
                    'name' => $break['name'],
                    'short_name' => $break['short_name'],
                    'after_period' => $number,
                    'start_time' => $clock->format('H:i:s'),
                    'end_time' => $breakEnd->format('H:i:s'),
                ];

                $clock = $breakEnd;
            }
        }

        return ['periods' => $periods, 'breaks' => $placedBreaks];
    }

    /**
     * @throws DayStructureRefused
     */
    private function refuseIfCardsWouldBeLost(Setting $setting, int $periodsPerDay, int $daysPerCycle): void
    {
        $cards = Card::query()
            ->whereHas('lesson', fn ($q) => $q->where('tt_setting_id', $setting->id))
            ->get(['id', 'period_number', 'days']);

        $periods = [];
        $days = [];

        foreach ($cards as $card) {
            $number = (int) $card->period_number;

            if ($number > $periodsPerDay) {
                $periods[$number] = ($periods[$number] ?? 0) + 1;
            }

            // Read the mask at its stored width, not at the new cycle length — the whole
            // question is which bits the new, shorter cycle would drop off the end.
            $mask = new Bitmask((string) $card->days, max(1, strlen((string) $card->days)));

            foreach ($mask->positions() as $day) {
                if ($day > $daysPerCycle) {
                    $days[$day] = ($days[$day] ?? 0) + 1;
                }
            }
        }

        if ($periods !== [] || $days !== []) {
            ksort($periods);
            ksort($days);

            throw new DayStructureRefused($periods, $days);
        }
    }

    /**
     * @param  list<array{after_period: int, minutes?: int, name?: string, short_name?: string}>  $breaks
     * @return list<array{after_period: int, minutes: int, name: string, short_name: string}>
     */
    private function normaliseBreaks(array $breaks, int $periodsPerDay): array
    {
        $normalised = [];

        foreach ($breaks as $break) {
            $after = (int) ($break['after_period'] ?? 0);

            // A break after the last period is just an early home time, not a break.
            if ($after < 1 || $after >= $periodsPerDay) {
                continue;
            }

            $normalised[$after] = [
                'after_period' => $after,
                'minutes' => max(1, (int) ($break['minutes'] ?? self::DEFAULT_BREAK_MINUTES)),
                'name' => trim((string) ($break['name'] ?? '')) ?: 'Break',
                'short_name' => trim((string) ($break['short_name'] ?? '')) ?: 'BK',
            ];
        }

        ksort($normalised);

        return array_values($normalised);
    }

    /**
     * Accepts 07:30 and 07:30:00 alike — the form posts the former, the database the latter.
     */
    private function asHourMinute(string $time): string
    {
        return substr(trim($time), 0, 5);
    }
}
