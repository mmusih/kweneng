<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $number = Room::query()->count() + 1;

        return [
            'tt_setting_id' => Setting::factory(),
            'name' => 'Room '.$number,
            'short_name' => 'R'.$number,
            'capacity' => 40,
            'asc_id' => null,
        ];
    }
}
