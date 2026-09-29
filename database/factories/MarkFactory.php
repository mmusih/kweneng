<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarkFactory extends Factory
{
    protected $model = Mark::class;

    public function definition(): array
    {
        $midterm = $this->faker->randomFloat(2, 0, 100);
        $endterm = $this->faker->randomFloat(2, 0, 100);
        $average = ($midterm + $endterm) / 2;

        $grade = match (true) {
            $average > 89 => 'A*',
            $average > 79 => 'A',
            $average > 69 => 'B',
            $average > 59 => 'C',
            $average > 49 => 'D',
            $average > 39 => 'E',
            $average > 34 => 'F',
            default => 'G',
        };

        return [
            'student_id' => Student::inRandomOrder()->first()->id,
            'subject_id' => Subject::inRandomOrder()->first()->id,
            'class_id' => ClassModel::inRandomOrder()->first()->id,
            'teacher_id' => Teacher::inRandomOrder()->first()->id,
            'academic_year_id' => AcademicYear::inRandomOrder()->first()->id,
            'term_id' => Term::inRandomOrder()->first()->id,
            'midterm_score' => $midterm,
            'endterm_score' => $endterm,
            'grade' => $grade,
            'remarks' => $this->faker->sentence(),
        ];
    }
}
