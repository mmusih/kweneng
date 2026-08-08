<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\DaysDef;
use App\Models\Tt\Setting;

class DaysDefFactory extends Factory
{
    protected $model = DaysDef::class;

    // "Any day" carries one 6-wide mask per cycle day, comma separated: the
    // solver may place the card on any single day of the rotation.
    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'name' => 'Any day',
            'short_name' => 'Any',
            'days' => implode(',', self::singleDayMasks()),
            'asc_id' => null,
        ];
    }

    protected static function singleDayMasks(): array
    {
        $masks = [];

        for ($day = 0; $day < SettingFactory::CYCLE_LENGTH; $day++) {
            $mask = str_repeat('0', SettingFactory::CYCLE_LENGTH);
            $mask[$day] = '1';
            $masks[] = $mask;
        }

        return $masks;
    }
}
