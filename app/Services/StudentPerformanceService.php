<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\Student;
use App\Models\Term;

class StudentPerformanceService
{
    public function __construct(
        protected ExamSummaryService $examSummaryService,
        protected MarksService $marksService
    ) {}

    public function getStudentTermPerformance(Student $student, int $academicYearId, int $termId): array
    {
        $classId = $student->current_class_id;

        if (!$classId) {
            return $this->emptyPerformance();
        }

        $midtermAverage = $this->calculateExamAverage($student->id, $academicYearId, $termId, ExamSummaryService::EXAM_MIDTERM);
        $endtermAverage = $this->calculateExamAverage($student->id, $academicYearId, $termId, ExamSummaryService::EXAM_ENDTERM);

        $midtermPosition = $this->examSummaryService->getStudentPosition(
            $student->id,
            $classId,
            $academicYearId,
            $termId,
            ExamSummaryService::EXAM_MIDTERM
        );

        $endtermPosition = $this->examSummaryService->getStudentPosition(
            $student->id,
            $classId,
            $academicYearId,
            $termId,
            ExamSummaryService::EXAM_ENDTERM
        );

        return [
            'midterm_average' => $midtermAverage,
            'endterm_average' => $endtermAverage,
            'midterm_grade' => $midtermAverage !== null ? $this->marksService->calculateGrade($midtermAverage) : null,
            'endterm_grade' => $endtermAverage !== null ? $this->marksService->calculateGrade($endtermAverage) : null,
            'midterm_position' => $midtermPosition,
            'endterm_position' => $endtermPosition,
            'performance_label' => $this->performanceLabel($endtermAverage ?? $midtermAverage),
            'trend' => $this->trendLabel($midtermAverage, $endtermAverage),
        ];
    }

    protected function calculateExamAverage(int $studentId, int $academicYearId, int $termId, string $examType): ?float
    {
        $marks = Mark::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $scores = $marks->map(function ($mark) use ($examType) {
            return $examType === ExamSummaryService::EXAM_MIDTERM
                ? $mark->midterm_score
                : $mark->endterm_score;
        })->filter(fn($score) => $score !== null)->values();

        if ($scores->isEmpty()) {
            return null;
        }

        return round($scores->avg(), 2);
    }

    public function performanceLabel(?float $average): ?string
    {
        if ($average === null) {
            return null;
        }

        if ($average >= 80) {
            return 'Excellent';
        }

        if ($average >= 70) {
            return 'Good';
        }

        if ($average >= 60) {
            return 'Satisfactory';
        }

        if ($average >= 50) {
            return 'Needs Improvement';
        }

        return 'At Risk';
    }

    public function trendLabel(?float $midtermAverage, ?float $endtermAverage): ?string
    {
        if ($midtermAverage === null || $endtermAverage === null) {
            return null;
        }

        $difference = round($endtermAverage - $midtermAverage, 2);

        if ($difference >= 2) {
            return 'Improving';
        }

        if ($difference <= -2) {
            return 'Declining';
        }

        return 'Stable';
    }

    protected function emptyPerformance(): array
    {
        return [
            'midterm_average' => null,
            'endterm_average' => null,
            'midterm_grade' => null,
            'endterm_grade' => null,
            'midterm_position' => null,
            'endterm_position' => null,
            'performance_label' => null,
            'trend' => null,
        ];
    }
}
