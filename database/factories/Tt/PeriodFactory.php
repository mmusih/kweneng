<?php

namespace Database\Factories\Tt;

use App\Models\Tt\Period;
use App\Models\Tt\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

class PeriodFactory extends Factory
{
    // Eight 40-minute periods, 07:30 to 13:10, with Breaktime 10:10-10:30 after period 4.
    public const TIMES = [
        1 => ['07:30:00', '08:10:00'],
        2 => ['08:10:00', '08:50:00'],
        3 => ['08:50:00', '09:30:00'],
        4 => ['09:30:00', '10:10:00'],
        5 => ['10:30:00', '11:10:00'],
        6 => ['11:10:00', '11:50:00'],
        7 => ['11:50:00', '12:30:00'],
        8 => ['12:30:00', '13:10:00'],
    ];

    protected $model = Period::class;

    /**
     * A full day of periods for one setting, laid down one row at a time.
     *
     * Period::factory()->count(8) cannot do this: it resolves every row's attributes
     * before the first insert, so all eight read the same max(period_number), all claim
     * period 1, and the unique index rejects the batch.
     *
     * @return \Illuminate\Support\Collection<int, Period>
     */
    public static function layDay(Setting $setting, int $periods = 8): Collection
    {
        return collect(range(1, $periods))->map(fn (int $number) => Period::create([
            'tt_setting_id' => $setting->id,
            'period_number' => $number,
            'name' => 'Period '.$number,
            'short_name' => (string) $number,
            'start_time' => self::timesFor($number)[0],
            'end_time' => self::timesFor($number)[1],
        ]));
    }

    // period_number fills the next free slot in the owning setting — one row per call.
    // For a whole day use layDay() above.
    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'period_number' => fn (array $attributes) => (int) Period::query()
                ->where('tt_setting_id', $attributes['tt_setting_id'])
                ->max('period_number') + 1,
            'name' => fn (array $attributes) => 'Period '.$attributes['period_number'],
            'short_name' => fn (array $attributes) => (string) $attributes['period_number'],
            'start_time' => fn (array $attributes) => self::timesFor($attributes['period_number'])[0],
            'end_time' => fn (array $attributes) => self::timesFor($attributes['period_number'])[1],
        ];
    }

    protected static function timesFor(int $periodNumber): array
    {
        return self::TIMES[$periodNumber] ?? self::TIMES[count(self::TIMES)];
    }
}
