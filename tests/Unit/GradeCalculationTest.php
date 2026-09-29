<?php

namespace Tests\Unit;

use App\Services\MarksService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GradeCalculationTest extends TestCase
{
    #[DataProvider('gradeCases')]
    public function test_grades_match_the_supplied_excel_formula(float $score, string $grade): void
    {
        $this->assertSame($grade, app(MarksService::class)->calculateGrade($score));
    }

    public static function gradeCases(): array
    {
        return [
            'maximum A star' => [100, 'A*'],
            'decimal A star boundary' => [89.01, 'A*'],
            'A boundary' => [89, 'A'],
            'decimal A boundary' => [79.01, 'A'],
            'B boundary' => [79, 'B'],
            'decimal B boundary' => [69.01, 'B'],
            'C boundary' => [69, 'C'],
            'decimal C boundary' => [59.01, 'C'],
            'D boundary' => [59, 'D'],
            'decimal D boundary' => [49.01, 'D'],
            'E boundary' => [49, 'E'],
            'decimal E boundary' => [39.01, 'E'],
            'F upper boundary' => [39, 'F'],
            'F lower boundary' => [35, 'F'],
            'decimal F boundary' => [34.01, 'F'],
            'G boundary' => [34, 'G'],
            'zero is G' => [0, 'G'],
        ];
    }

    public function test_overall_grade_uses_the_available_score_average(): void
    {
        $service = app(MarksService::class);

        $this->assertSame('A', $service->calculateGradeForScores(90, 70));
        $this->assertSame('F', $service->calculateGradeForScores(35, null));
        $this->assertSame('G', $service->calculateGradeForScores(null, 34));
        $this->assertNull($service->calculateGradeForScores(null, null));
    }
}
