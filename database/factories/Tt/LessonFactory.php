<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Subject;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    // The common shape is a double: 6.0 periods a week taught as three
    // back-to-back pairs. The defs stay null so callers can attach the
    // daysdef/weeksdef/termsdef belonging to the same setting.
    public function definition(): array
    {
        return [
            'tt_setting_id' => Setting::factory(),
            'subject_id' => $this->subjectId(),
            'periods_per_week' => 6.0,
            'periods_per_card' => 2,
            'tt_daysdef_id' => null,
            'tt_weeksdef_id' => null,
            'tt_termsdef_id' => null,
            'seminar_group' => null,
            'capacity' => null,
            'asc_id' => null,
        ];
    }

    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'periods_per_week' => 5.0,
            'periods_per_card' => 1,
        ]);
    }

    public function double(): static
    {
        return $this->state(fn (array $attributes) => [
            'periods_per_week' => 6.0,
            'periods_per_card' => 2,
        ]);
    }

    protected function subjectId(): int
    {
        return Subject::query()->value('id')
            ?? Subject::create([
                'name' => 'Mathematics',
                'code' => 'MATH',
                'description' => 'Core Mathematics',
                'is_core' => true,
                'is_active' => true,
                'display_order' => 1,
            ])->id;
    }
}
