<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Setting;

class BreakPeriodFactory extends Factory
{
    protected $model = BreakPeriod::class;

    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'name' => 'Breaktime',
            'short_name' => 'Break',
            'start_time' => '10:10:00',
            'end_time' => '10:30:00',
            'after_period' => 4,
            'days' => str_repeat('1', SettingFactory::CYCLE_LENGTH),
            'asc_id' => null,
        ];
    }
}
