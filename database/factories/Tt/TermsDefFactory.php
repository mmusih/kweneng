<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\Setting;
use App\Models\Tt\TermsDef;

class TermsDefFactory extends Factory
{
    protected $model = TermsDef::class;

    // One timetable covers the whole year, so there is a single term in the mask.
    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'name' => 'Whole year',
            'short_name' => 'Year',
            'terms' => '1',
            'asc_id' => null,
        ];
    }
}
