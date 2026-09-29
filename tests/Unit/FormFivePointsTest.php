<?php

namespace Tests\Unit;

use App\Models\ClassModel;
use App\Services\MarksService;
use PHPUnit\Framework\TestCase;

class FormFivePointsTest extends TestCase
{
    private MarksService $marksService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marksService = new MarksService;
    }

    public function test_score_bands_match_the_supplied_excel_formula(): void
    {
        $expectedPoints = [
            100 => 8,
            90 => 8,
            89 => 8,
            80 => 8,
            79 => 7,
            70 => 7,
            69 => 6,
            60 => 6,
            59 => 5,
            50 => 5,
            49 => 4,
            40 => 4,
            39 => 0,
            35 => 0,
            34 => 0,
            0 => 0,
        ];

        foreach ($expectedPoints as $score => $points) {
            $this->assertSame(
                $points,
                $this->marksService->calculateFormFiveSubjectPoints((float) $score),
                "Unexpected points for a score of {$score}."
            );
        }

        $this->assertSame(8, $this->marksService->calculateFormFiveSubjectPoints(79.01));
        $this->assertSame(7, $this->marksService->calculateFormFiveSubjectPoints(69.01));
        $this->assertSame(6, $this->marksService->calculateFormFiveSubjectPoints(59.01));
        $this->assertSame(5, $this->marksService->calculateFormFiveSubjectPoints(49.01));
        $this->assertSame(4, $this->marksService->calculateFormFiveSubjectPoints(39.01));
    }

    public function test_best_six_always_includes_english_and_mathematics(): void
    {
        $result = $this->marksService->calculateFormFiveBestSix([
            $this->subject('English as a Second Language', 'Esl', 40),
            $this->subject('Extended Mathematics', 'MaE', 35),
            $this->subject('Biology', 'BIO', 95),
            $this->subject('Physics', 'PHY', 90),
            $this->subject('Chemistry', 'CHEM', 85),
            $this->subject('Geography', 'Geo', 80),
            $this->subject('Accounting', 'Acc', 75),
            $this->subject('Business Studies', 'Bs', 65),
        ], 'endterm_score');

        $this->assertTrue($result['complete']);
        $this->assertSame(36, $result['total']);
        $this->assertSame('36/48', $result['display']);
        $this->assertCount(6, $result['selected_subjects']);
        $this->assertSame(
            ['Esl', 'MaE', 'BIO', 'PHY', 'CHEM', 'Geo'],
            collect($result['selected_subjects'])->pluck('subject_code')->all()
        );
    }

    public function test_points_are_pending_when_a_compulsory_subject_is_missing(): void
    {
        $result = $this->marksService->calculateFormFiveBestSix([
            $this->subject('English First Language', 'EFL', 80),
            $this->subject('Biology', 'BIO', 80),
            $this->subject('Physics', 'PHY', 80),
            $this->subject('Chemistry', 'CHEM', 80),
            $this->subject('Geography', 'Geo', 80),
            $this->subject('Accounting', 'Acc', 80),
        ], 'endterm_score');

        $this->assertFalse($result['complete']);
        $this->assertNull($result['total']);
        $this->assertContains('Mathematics (MaC/MaE)', $result['missing']);
    }

    public function test_report_points_are_only_calculated_for_form_five(): void
    {
        $subjects = [
            $this->subject('English First Language', 'EFL', 80),
            $this->subject('Core Mathematics', 'MaC', 80),
        ];

        $this->assertNull(
            $this->marksService->calculateFormFiveReportPoints(
                new ClassModel(['name' => 'Form 4A', 'level' => 11]),
                $subjects
            )
        );

        $this->assertNotNull(
            $this->marksService->calculateFormFiveReportPoints(
                new ClassModel(['name' => 'Form 5A', 'level' => 12]),
                $subjects
            )
        );
    }

    private function subject(string $name, string $code, float $score): array
    {
        return [
            'subject_name' => $name,
            'subject_code' => $code,
            'midterm_score' => $score,
            'endterm_score' => $score,
        ];
    }
}
