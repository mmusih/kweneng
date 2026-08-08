<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\Card;
use App\Models\Tt\Lesson;
use App\Models\Tt\Room;

class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'tt_lesson_id' => Lesson::factory(),
            'period_number' => $this->faker->numberBetween(1, count(PeriodFactory::TIMES)),
            'days' => self::dayMask(0),
            'weeks' => '1',
            'terms' => '1',
            'tt_room_id' => null,
            'locked' => false,
        ];
    }

    // $day is a 0-based cycle day index: 0 => "100000", 5 => "000001".
    public function onDay(int $day): static
    {
        $mask = self::dayMask($day);

        return $this->state(fn (array $attributes) => [
            'days' => $mask,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked' => true,
        ]);
    }

    public function placed(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'tt_room_id' => $room->id,
        ]);
    }

    protected static function dayMask(int $day): string
    {
        $day = max(0, min($day, SettingFactory::CYCLE_LENGTH - 1));

        $mask = str_repeat('0', SettingFactory::CYCLE_LENGTH);
        $mask[$day] = '1';

        return $mask;
    }
}
