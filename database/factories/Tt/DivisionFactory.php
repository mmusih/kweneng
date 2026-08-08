<?php

namespace Database\Factories\Tt;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Tt\Division;

class DivisionFactory extends Factory
{
    protected $model = Division::class;

    // division_tag takes the next free tag within the class, so repeated
    // calls for one class never collide on tt_divisions_class_tag_unique.
    public function definition(): array
    {
        return [
            'class_id' => $this->classId(),
            'division_tag' => fn (array $attributes) => (int) Division::query()
                ->where('class_id', $attributes['class_id'])
                ->max('division_tag') + 1,
            'name' => fn (array $attributes) => 'Division '.$attributes['division_tag'],
        ];
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
