<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Tt\Group;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'class_id' => $this->classId(),
            'tt_division_id' => null,
            'name' => fn (array $attributes) => 'Group '.(Group::query()
                ->where('class_id', $attributes['class_id'])
                ->count() + 1),
            'entire_class' => false,
            'asc_id' => null,
            'partner_id' => null,
        ];
    }

    // The undivided class: every student takes the lesson, so it sits in no division.
    // name resolves lazily because a caller using ->for() leaves class_id unresolved here.
    public function entireClass(): static
    {
        return $this->state(fn (array $attributes) => [
            'tt_division_id' => null,
            'entire_class' => true,
            'name' => fn (array $attrs) => ClassModel::query()
                ->whereKey($attrs['class_id'])
                ->value('name') ?? 'Entire class',
        ]);
    }

    protected function classId(): int
    {
        return ClassModel::query()->value('id')
            ?? ClassModel::create([
                'name' => 'Form 1A',
                'level' => 8,
                'academic_year_id' => AcademicYear::current()?->id
                    ?? AcademicYear::query()->value('id'),
            ])->id;
    }
}
