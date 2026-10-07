<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AcademicYear;
use App\Models\Tt\Setting;

class SettingFactory extends Factory
{
    // The school runs a 6-day rotating cycle, so every days mask is 6 characters wide.
    public const CYCLE_LENGTH = 6;

    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => $this->academicYearId(),
            'name' => 'Master Timetable',
            'term_label' => 'Term 1',
            'revision' => 1,
            'cycle_length' => self::CYCLE_LENGTH,
            'asc_options' => null,
            'is_active' => true,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'is_published' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_published' => false,
        ]);
    }

    protected function academicYearId(): int
    {
        return AcademicYear::current()?->id
            ?? AcademicYear::query()->value('id')
            ?? AcademicYear::create([
                'year_name' => '2026',
                'active' => true,
                'status' => AcademicYear::STATUS_OPEN,
            ])->id;
    }
}
