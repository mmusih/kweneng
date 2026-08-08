<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\Setting;
use App\Models\Tt\WeeksDef;

class WeeksDefFactory extends Factory
{
    protected $model = WeeksDef::class;

    // The school does not alternate weeks, so there is a single week in the mask.
    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'name' => 'All weeks',
            'short_name' => 'All',
            'weeks' => '1',
            'asc_id' => null,
        ];
    }
}
