<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Tt\Constraint;
use App\Models\Tt\Setting;

class ConstraintFactory extends Factory
{
    protected $model = Constraint::class;

    // params stays empty: each kind defines its own payload, so callers set it.
    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'kind' => 'teacher_not_available',
            'weight' => Constraint::WEIGHT_HARD,
            'params' => [],
            'is_active' => true,
        ];
    }
}
