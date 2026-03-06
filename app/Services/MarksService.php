<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\StudentSubject;
use App\Models\TeacherSubject;
use App\Models\StudentClassHistory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarksService
{
    /**
     * Calculate grade based on average score
     */
    public function calculateGrade(float $average): string
    {
        if ($average >= 90) return 'A*';
        if ($average >= 80) return 'A';
        if ($average >= 70) return 'B';
        if ($average >= 60) return 'C';
        if ($average >= 50) return 'D';
        if ($average >= 40) return 'E';
        return 'F';
    }

    /**
     * Insert or update marks safely
     */
    public function upsertMarks(
        int $studentId,
        int $subjectId,
        int $classId,
        int $teacherId,
        int $academicYearId,
        int $termId,
        ?float $midtermScore,
        ?float $endtermScore,
        ?string $remarks = null
    ): Mark {
        // Validate scores
        if ($midtermScore !== null && ($midtermScore < 0 || $midtermScore > 100)) {
            throw new InvalidArgumentException('Midterm score must be between 0 and 100');
        }
        
        if ($endtermScore !== null && ($endtermScore < 0 || $endtermScore > 100)) {
            throw new InvalidArgumentException('Endterm score must be between 0 and 100');
        }

        // Calculate average and grade
        $average = null;
        if ($midtermScore !== null && $endtermScore !== null) {
            $average = ($midtermScore + $endtermScore) / 2;
        } elseif ($midtermScore !== null) {
            $average = $midtermScore;
        } elseif ($endtermScore !== null) {
            $average = $endtermScore;
        }
        
        $grade = $average !== null ? $this->calculateGrade($average) : null;

        // Upsert mark
        return Mark::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
            ],
            [
                'class_id' => $classId,
                'teacher_id' => $teacherId,
                'midterm_score' => $midtermScore,
                'endterm_score' => $endtermScore,
                'grade' => $grade,
                'remarks' => $remarks,
            ]
        );
    }

    /**
     * Bulk upsert marks
     */
    public function bulkUpsertMarks(array $marksData): array
    {
        try {
            return DB::transaction(function () use ($marksData) {
                $results = [];
                $successCount = 0;
                $errorCount = 0;

                foreach ($marksData as $markData) {
                    try {
                        $mark = $this->upsertMarks(
                            $markData['student_id'],
                            $markData['subject_id'],
                            $markData['class_id'],
                            $markData['teacher_id'],
                            $markData['academic_year_id'],
                            $markData['term_id'],
                            $markData['midterm_score'] ?? null,
                            $markData['endterm_score'] ?? null,
                            $markData['remarks'] ?? null
                        );

                        $results[] = [
                            'success' => true,
                            'student_id' => $markData['student_id'],
                            'mark_id' => $mark->id,
                            'message' => 'Mark saved successfully'
                        ];
                        $successCount++;
                    } catch (\Exception $e) {
                        $results[] = [
                            'success' => false,
                            'student_id' => $markData['student_id'] ?? null,
                            'message' => $e->getMessage()
                        ];
                        $errorCount++;
                    }
                }

                return [
                    'success' => true,
                    'message' => "Bulk operation completed: {$successCount} succeeded, {$errorCount} failed",
                    'results' => $results,
                    'summary' => [
                        'total' => count($marksData),
                        'success' => $successCount,
                        'errors' => $errorCount
                    ]
                ];
            });
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage(),
                'results' => [],
                'summary' => [
                    'total' => count($marksData),
                    'success' => 0,
                    'errors' => count($marksData)
                ]
            ];
        }
    }

    /**
     * Get students for marks entry
     */
    public function getStudentsForMarksEntry(int $classId, int $subjectId, int $academicYearId): array
    {
        // Get actively enrolled students in the class
        $activeEnrollments = StudentClassHistory::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->whereNull('exited_at')
            ->with('student.user')
            ->get();

        $eligibleStudents = [];

        foreach ($activeEnrollments as $enrollment) {
            // Check if student is assigned to this subject
            $subjectAssigned = StudentSubject::where('student_id', $enrollment->student_id)
                ->where('subject_id', $subjectId)
                ->where('academic_year_id', $academicYearId)
                ->exists();

            if ($subjectAssigned) {
                $eligibleStudents[] = [
                    'student' => $enrollment->student,
                    'user' => $enrollment->student->user,
                    'enrollment' => $enrollment
                ];
            }
        }

        return $eligibleStudents;
    }

    /**
     * Get teacher's classes for marks entry
     */
    public function getTeacherClassesForMarks(Teacher $teacher): array
    {
        return $teacher->teacherSubjects()
            ->with(['class', 'subject', 'academicYear'])
            ->get()
            ->groupBy('class_id')
            ->map(function ($subjects) {
                return [
                    'class' => $subjects->first()->class,
                    'subjects' => $subjects->pluck('subject')->unique('id'),
                    'academic_year' => $subjects->first()->academicYear
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Calculate student averages for a term
     */
    public function calculateStudentAverages(int $studentId, int $termId): array
    {
        $marks = Mark::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();

        $markedSubjects = $marks->count();
        $totalSubjects = StudentSubject::where('student_id', $studentId)
            ->whereHas('academicYear', function($query) use ($termId) {
                $query->where('id', Term::find($termId)->academic_year_id);
            })
            ->count();

        $midtermScores = $marks->pluck('midterm_score')->filter(fn($score) => $score !== null);
        $endtermScores = $marks->pluck('endterm_score')->filter(fn($score) => $score !== null);

        $midtermAverage = $midtermScores->isNotEmpty() ? $midtermScores->avg() : null;
        $endtermAverage = $endtermScores->isNotEmpty() ? $endtermScores->avg() : null;

        return [
            'midterm_average' => $midtermAverage,
            'endterm_average' => $endtermAverage,
            'completion_ratio' => $totalSubjects > 0 ? "{$markedSubjects}/{$totalSubjects}" : "0/0",
            'marked_subjects' => $markedSubjects,
            'total_subjects' => $totalSubjects
        ];
    }

    /**
     * Validate marks entry permissions
     */
    public function validateMarksEntry(
        Teacher $teacher,
        int $classId,
        int $subjectId,
        int $academicYearId,
        int $termId
    ): bool {
        // Check if teacher is assigned to this subject/class
        $teacherAssignment = TeacherSubject::where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if (!$teacherAssignment) {
            return false;
        }

        // Check if term is active
        $term = Term::find($termId);
        if (!$term || $term->status !== 'active') {
            return false;
        }

        // Check if academic year is open or closed (not locked)
        $academicYear = AcademicYear::find($academicYearId);
        if (!$academicYear || $academicYear->status === 'locked') {
            return false;
        }

        return true;
    }
}
