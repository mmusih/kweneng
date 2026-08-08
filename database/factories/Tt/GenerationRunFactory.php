<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\GenerationRun;
use App\Models\Tt\Setting;

class GenerationRunFactory extends Factory
{
    protected $model = GenerationRun::class;

    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'status' => GenerationRun::STATUS_PENDING,
            'seed' => $this->faker->numberBetween(1, 999999),
            'placed_count' => 0,
            'total_count' => 0,
            'hard_violations' => 0,
            'soft_score' => 0,
            'log' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
