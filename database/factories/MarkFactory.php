<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Models\Term;

class MarkFactory extends Factory
{
    protected $model = Mark::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::inRandomOrder()->first()->id,
            'subject_id' => Subject::inRandomOrder()->first()->id,
            'class_id' => ClassModel::inRandomOrder()->first()->id,
            'teacher_id' => Teacher::inRandomOrder()->first()->id,
            'academic_year_id' => AcademicYear::inRandomOrder()->first()->id,
            'term_id' => Term::inRandomOrder()->first()->id,
            'midterm_score' => $this->faker->randomFloat(2, 0, 100),
            'endterm_score' => $this->faker->randomFloat(2, 0, 100),
            'grade' => $this->faker->randomElement(['A*', 'A', 'B', 'C', 'D', 'E', 'F']),
            'remarks' => $this->faker->sentence(),
        ];
    }
}
