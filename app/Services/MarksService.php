<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentSubject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarksService
{
    /**
     * Calculate grade based on average score.
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
     * Insert or update marks safely.
     *
     * This method deliberately validates the student-teacher assignment before
     * writing. Controllers and views can make mistakes, but the service must not
     * allow a teacher to save marks for a learner outside their assigned group.
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
        if (! $this->studentIsAssignedToTeacherForMarks($studentId, $classId, $subjectId, $academicYearId, $teacherId)) {
            throw new InvalidArgumentException('This learner is not assigned to this teacher for the selected class, subject, and academic year.');
        }

        if ($midtermScore !== null && ($midtermScore < 0 || $midtermScore > 100)) {
            throw new InvalidArgumentException('Midterm score must be between 0 and 100');
        }

        if ($endtermScore !== null && ($endtermScore < 0 || $endtermScore > 100)) {
            throw new InvalidArgumentException('Endterm score must be between 0 and 100');
        }

        $average = $this->calculateAverage($midtermScore, $endtermScore);
        $grade = $average !== null ? $this->calculateGrade($average) : null;

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
     * Bulk upsert marks.
     *
     * The operation is all-or-nothing: one invalid learner or database error
     * rolls back the whole submission. This prevents partial class mark sheets.
     */
    public function bulkUpsertMarks(array $marksData): array
    {
        if (count($marksData) === 0) {
            return [
                'success' => false,
                'message' => 'No marks were submitted.',
                'results' => [],
                'summary' => [
                    'total' => 0,
                    'success' => 0,
                    'errors' => 0,
                ],
            ];
        }

        try {
            $results = DB::transaction(function () use ($marksData) {
                $results = [];

                foreach ($marksData as $markData) {
                    $mark = $this->upsertMarks(
                        (int) $markData['student_id'],
                        (int) $markData['subject_id'],
                        (int) $markData['class_id'],
                        (int) $markData['teacher_id'],
                        (int) $markData['academic_year_id'],
                        (int) $markData['term_id'],
                        array_key_exists('midterm_score', $markData) && $markData['midterm_score'] !== null && $markData['midterm_score'] !== '' ? (float) $markData['midterm_score'] : null,
                        array_key_exists('endterm_score', $markData) && $markData['endterm_score'] !== null && $markData['endterm_score'] !== '' ? (float) $markData['endterm_score'] : null,
                        $markData['remarks'] ?? null
                    );

                    $results[] = [
                        'success' => true,
                        'student_id' => $markData['student_id'],
                        'mark_id' => $mark->id,
                        'message' => 'Mark saved successfully',
                    ];
                }

                return $results;
            });

            return [
                'success' => true,
                'message' => count($results) . ' mark record(s) saved successfully.',
                'results' => $results,
                'summary' => [
                    'total' => count($marksData),
                    'success' => count($results),
                    'errors' => 0,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Marks were not saved: ' . $e->getMessage(),
                'results' => [],
                'summary' => [
                    'total' => count($marksData),
                    'success' => 0,
                    'errors' => count($marksData),
                ],
            ];
        }
    }

    /**
     * Get students for marks/homework entry.
     *
     * When $teacherId is supplied, the result is strictly limited to learners
     * assigned to that teacher in student_subjects. This supports shared class
     * subjects, where multiple teachers split the same class into teaching groups.
     */
    public function getStudentsForMarksEntry(int $classId, int $subjectId, int $academicYearId, ?int $teacherId = null): array
    {
        $students = Student::query()
            ->with(['user'])
            ->whereHas('classHistory', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
            ->whereHas('studentSubjects', function ($query) use ($classId, $subjectId, $academicYearId, $teacherId) {
                $query->where('class_id', $classId)
                    ->where('subject_id', $subjectId)
                    ->where('academic_year_id', $academicYearId);

                if ($teacherId !== null) {
                    $query->where('teacher_id', $teacherId);
                }
            })
            ->join('users', 'users.id', '=', 'students.user_id')
            ->orderBy('users.name')
            ->select('students.*')
            ->get();

        $enrollments = StudentClassHistory::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'active')
            ->whereNull('exited_at')
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return $students->map(function (Student $student) use ($enrollments) {
            return [
                'student' => $student,
                'user' => $student->user,
                'enrollment' => $enrollments->get($student->id),
            ];
        })->values()->all();
    }

    /**
     * Return IDs for learners a teacher may assess for a class-subject-year.
     */
    public function getAuthorizedStudentIdsForMarks(Teacher $teacher, int $classId, int $subjectId, int $academicYearId): Collection
    {
        return StudentSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->whereHas('student.classHistory', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    public function studentIsAssignedToTeacherForMarks(int $studentId, int $classId, int $subjectId, int $academicYearId, int $teacherId): bool
    {
        return StudentSubject::query()
            ->where('student_id', $studentId)
            ->where('teacher_id', $teacherId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->whereHas('student.classHistory', function ($query) use ($classId, $academicYearId) {
                $query->where('class_id', $classId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
            ->exists();
    }

    /**
     * Get teacher's classes for marks entry.
     */
    public function getTeacherClassesForMarks(Teacher $teacher, ?int $academicYearId = null): array
    {
        $academicYearId ??= AcademicYear::current()?->id;

        return $teacher->teacherSubjects()
            ->with(['class', 'subject', 'academicYear'])
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->get()
            ->filter(fn ($assignment) => $assignment->class && $assignment->subject && $assignment->academicYear)
            ->groupBy(fn ($assignment) => $assignment->class_id . ':' . $assignment->academic_year_id)
            ->map(function ($subjects) {
                return [
                    'class' => $subjects->first()->class,
                    'subjects' => $subjects->pluck('subject')->unique('id')->values(),
                    'academic_year' => $subjects->first()->academicYear,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Calculate student averages for a term.
     */
    public function calculateStudentAverages(int $studentId, int $termId): array
    {
        $term = Term::find($termId);

        if (! $term) {
            return [
                'midterm_average' => null,
                'endterm_average' => null,
                'completion_ratio' => '0/0',
                'marked_subjects' => 0,
                'total_subjects' => 0,
            ];
        }

        $marks = Mark::where('student_id', $studentId)
            ->where('term_id', $termId)
            ->get();

        $markedSubjects = $marks->count();
        $totalSubjects = StudentSubject::where('student_id', $studentId)
            ->where('academic_year_id', $term->academic_year_id)
            ->count();

        $midtermScores = $marks->pluck('midterm_score')->filter(fn ($score) => $score !== null);
        $endtermScores = $marks->pluck('endterm_score')->filter(fn ($score) => $score !== null);

        return [
            'midterm_average' => $midtermScores->isNotEmpty() ? $midtermScores->avg() : null,
            'endterm_average' => $endtermScores->isNotEmpty() ? $endtermScores->avg() : null,
            'completion_ratio' => $totalSubjects > 0 ? "{$markedSubjects}/{$totalSubjects}" : '0/0',
            'marked_subjects' => $markedSubjects,
            'total_subjects' => $totalSubjects,
        ];
    }

    /**
     * Validate class/subject/term access for a teacher.
     */
    public function validateMarksEntry(
        Teacher $teacher,
        int $classId,
        int $subjectId,
        int $academicYearId,
        int $termId
    ): bool {
        $teacherAssignment = TeacherSubject::where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->exists();

        if (! $teacherAssignment) {
            return false;
        }

        $term = Term::find($termId);
        if (! $term || (int) $term->academic_year_id !== $academicYearId || $term->status !== 'active') {
            return false;
        }

        $academicYear = AcademicYear::find($academicYearId);
        if (! $academicYear || $academicYear->status === 'locked') {
            return false;
        }

        return true;
    }

    private function calculateAverage(?float $midtermScore, ?float $endtermScore): ?float
    {
        if ($midtermScore !== null && $endtermScore !== null) {
            return ($midtermScore + $endtermScore) / 2;
        }

        if ($midtermScore !== null) {
            return $midtermScore;
        }

        if ($endtermScore !== null) {
            return $endtermScore;
        }

        return null;
    }
}
