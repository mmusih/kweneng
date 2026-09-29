<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassModel;
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
        if ($average > 89) {
            return 'A*';
        }
        if ($average > 79) {
            return 'A';
        }
        if ($average > 69) {
            return 'B';
        }
        if ($average > 59) {
            return 'C';
        }
        if ($average > 49) {
            return 'D';
        }
        if ($average > 39) {
            return 'E';
        }
        if ($average > 34) {
            return 'F';
        }

        return 'G';
    }

    /**
     * Calculate the overall grade for the marks stored in one subject row.
     */
    public function calculateGradeForScores(?float $midtermScore, ?float $endtermScore): ?string
    {
        $average = $this->calculateAverage($midtermScore, $endtermScore);

        return $average !== null ? $this->calculateGrade($average) : null;
    }

    /**
     * Convert a Form 5 percentage mark to the school's points scale.
     */
    public function calculateFormFiveSubjectPoints(float $score): int
    {
        if ($score > 79) {
            return 8;
        }
        if ($score > 69) {
            return 7;
        }
        if ($score > 59) {
            return 6;
        }
        if ($score > 49) {
            return 5;
        }
        if ($score > 39) {
            return 4;
        }

        return 0;
    }

    /**
     * Calculate midterm and endterm best-six points for Form 5 only.
     */
    public function calculateFormFiveReportPoints(?ClassModel $class, iterable $subjects): ?array
    {
        $isFormFive = $class
            && ((int) $class->level === 12 || preg_match('/\bform\s*5\b/i', (string) $class->name) === 1);

        if (! $isFormFive) {
            return null;
        }

        return [
            'midterm' => $this->calculateFormFiveBestSix($subjects, 'midterm_score'),
            'endterm' => $this->calculateFormFiveBestSix($subjects, 'endterm_score'),
        ];
    }

    /**
     * English (EFL/ESL) and Mathematics (MaC/MaE) are compulsory. The four
     * remaining places are filled by the strongest point-scoring subjects.
     */
    public function calculateFormFiveBestSix(iterable $subjects, string $scoreKey): array
    {
        if (! in_array($scoreKey, ['midterm_score', 'endterm_score'], true)) {
            throw new InvalidArgumentException('Points can only be calculated from midterm or endterm scores.');
        }

        $scoredSubjects = collect($subjects)
            ->map(function ($subject, $index) use ($scoreKey) {
                $score = data_get($subject, $scoreKey);

                if ($score === null || $score === '') {
                    return null;
                }

                $code = strtoupper((string) preg_replace(
                    '/[^A-Z0-9]/i',
                    '',
                    (string) data_get($subject, 'subject_code', '')
                ));
                $score = (float) $score;

                return [
                    'key' => $index,
                    'subject_name' => (string) data_get($subject, 'subject_name', 'Unknown Subject'),
                    'subject_code' => (string) data_get($subject, 'subject_code', ''),
                    'score' => $score,
                    'points' => $this->calculateFormFiveSubjectPoints($score),
                    'is_english' => in_array($code, ['EFL', 'ESL'], true),
                    'is_mathematics' => in_array($code, ['MAC', 'MAE'], true),
                ];
            })
            ->filter()
            ->sort(function (array $left, array $right) {
                return ($right['points'] <=> $left['points'])
                    ?: ($right['score'] <=> $left['score'])
                    ?: strcmp($left['subject_name'], $right['subject_name']);
            })
            ->values();

        $english = $scoredSubjects->firstWhere('is_english', true);
        $mathematics = $scoredSubjects->firstWhere('is_mathematics', true);
        $missing = [];

        if (! $english) {
            $missing[] = 'English (EFL/ESL)';
        }

        if (! $mathematics) {
            $missing[] = 'Mathematics (MaC/MaE)';
        }

        $compulsory = collect([$english, $mathematics])->filter()->unique('key')->values();
        $remaining = $scoredSubjects
            ->reject(fn (array $subject) => $subject['is_english'] || $subject['is_mathematics'])
            ->take(max(0, 6 - $compulsory->count()));
        $selected = $compulsory
            ->concat($remaining)
            ->take(6);

        if ($selected->count() < 6) {
            $missing[] = (6 - $selected->count()).' additional scored subject(s)';
        }

        $selected = $selected
            ->map(function (array $subject) {
                $subject['compulsory'] = match (true) {
                    $subject['is_english'] => 'English',
                    $subject['is_mathematics'] => 'Mathematics',
                    default => null,
                };

                unset($subject['key'], $subject['is_english'], $subject['is_mathematics']);

                return $subject;
            })
            ->values();

        $complete = $missing === [] && $selected->count() === 6;
        $total = $complete ? (int) $selected->sum('points') : null;

        return [
            'complete' => $complete,
            'total' => $total,
            'maximum' => 48,
            'display' => $complete ? $total.'/48' : 'Pending',
            'missing' => $missing,
            'selected_subjects' => $selected->all(),
        ];
    }

    /**
     * Generate the subject teacher comment from the marks currently entered.
     */
    public function generateTeacherComment(?float $midtermScore, ?float $endtermScore): ?string
    {
        if ($midtermScore === null && $endtermScore === null) {
            return null;
        }

        if ($midtermScore !== null && $endtermScore === null) {
            return sprintf(
                'The learner showed %s in the midterm assessment but did not write the end-of-term assessment.',
                $this->performancePhrase($midtermScore)
            );
        }

        if ($midtermScore === null) {
            return sprintf(
                'The learner showed %s in the end-of-term assessment.',
                $this->performancePhrase($endtermScore)
            );
        }

        $difference = $endtermScore - $midtermScore;

        if ($difference >= 10) {
            return $endtermScore >= 60
                ? 'The learner has shown clear improvement since midterm. This progress is encouraging; continued effort is needed.'
                : 'The learner has improved since midterm, but more effort is still needed to reach the expected standard.';
        }

        if ($difference >= 3) {
            return $endtermScore >= 70
                ? 'The learner has improved and is making steady progress. More consistent effort can lead to even better results.'
                : 'The learner has shown some improvement since midterm. Continued practice is encouraged.';
        }

        if ($difference <= -10) {
            return 'The learner’s performance has declined significantly since midterm. Immediate improvement and greater commitment are required.';
        }

        if ($difference <= -3) {
            return 'The learner’s performance has dropped since midterm. More focus and consistency are needed.';
        }

        if ($endtermScore >= 80) {
            return 'The learner has maintained a very good standard throughout the term. Keep up the good work.';
        }

        if ($endtermScore >= 60) {
            return 'The learner’s performance has remained fairly steady. More effort can lead to better results.';
        }

        if ($endtermScore >= 40) {
            return 'The learner’s performance remains below expectation. More effort and support are required.';
        }

        return 'The learner’s performance is weak and requires immediate improvement.';
    }

    /**
     * Identify comments owned by the automatic generator, including stale
     * midterm-only comments saved before an endterm score was entered.
     */
    public function isGeneratedTeacherComment(?string $comment): bool
    {
        $comment = $comment !== null ? trim($comment) : '';

        if ($comment === '') {
            return false;
        }

        $comparisonComments = [
            'The learner has shown clear improvement since midterm. This progress is encouraging; continued effort is needed.',
            'The learner has improved since midterm, but more effort is still needed to reach the expected standard.',
            'The learner has improved and is making steady progress. More consistent effort can lead to even better results.',
            'The learner has shown some improvement since midterm. Continued practice is encouraged.',
            'The learner’s performance has declined significantly since midterm. Immediate improvement and greater commitment are required.',
            'The learner’s performance has dropped since midterm. More focus and consistency are needed.',
            'The learner has maintained a very good standard throughout the term. Keep up the good work.',
            'The learner’s performance has remained fairly steady. More effort can lead to better results.',
            'The learner’s performance remains below expectation. More effort and support are required.',
            'The learner’s performance is weak and requires immediate improvement.',
        ];

        if (in_array($comment, $comparisonComments, true)) {
            return true;
        }

        $phrases = implode('|', [
            'excellent performance',
            'very good performance',
            'good performance',
            'fair performance',
            'satisfactory performance',
            'below expectation performance',
            'weak performance',
        ]);

        return preg_match(
            '/^The learner showed (?:'.$phrases.') in the (?:midterm assessment but did not write the end-of-term assessment|end-of-term assessment)\.$/u',
            $comment
        ) === 1;
    }

    /**
     * Refresh automatic comments while preserving text deliberately entered by
     * a teacher. Comparing with the comment generated from the previous marks
     * lets an automatically saved comment remain automatic after page reloads.
     */
    public function resolveTeacherComment(
        ?float $midtermScore,
        ?float $endtermScore,
        ?string $incomingRemarks,
        ?float $previousMidtermScore = null,
        ?float $previousEndtermScore = null,
        ?string $previousRemarks = null
    ): ?string {
        $incomingRemarks = $incomingRemarks !== null ? trim($incomingRemarks) : null;
        $previousRemarks = $previousRemarks !== null ? trim($previousRemarks) : null;
        $wasAutomatic = $previousRemarks !== null
            && $previousRemarks !== ''
            && $incomingRemarks === $previousRemarks
            && $this->isGeneratedTeacherComment($previousRemarks);

        if ($incomingRemarks === null || $incomingRemarks === '' || $wasAutomatic) {
            return $this->generateTeacherComment($midtermScore, $endtermScore);
        }

        return $incomingRemarks;
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

        $existingMark = Mark::query()
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->first();

        $grade = $this->calculateGradeForScores($midtermScore, $endtermScore);
        $resolvedRemarks = $this->resolveTeacherComment(
            $midtermScore,
            $endtermScore,
            $remarks,
            $existingMark?->midterm_score !== null ? (float) $existingMark->midterm_score : null,
            $existingMark?->endterm_score !== null ? (float) $existingMark->endterm_score : null,
            $existingMark?->remarks
        );

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
                'remarks' => $resolvedRemarks,
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
                'message' => count($results).' mark record(s) saved successfully.',
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
                'message' => 'Marks were not saved: '.$e->getMessage(),
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
            ->groupBy(fn ($assignment) => $assignment->class_id.':'.$assignment->academic_year_id)
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

    private function performancePhrase(float $score): string
    {
        if ($score >= 90) {
            return 'excellent performance';
        }
        if ($score >= 80) {
            return 'very good performance';
        }
        if ($score >= 70) {
            return 'good performance';
        }
        if ($score >= 60) {
            return 'fair performance';
        }
        if ($score >= 50) {
            return 'satisfactory performance';
        }
        if ($score >= 40) {
            return 'below expectation performance';
        }

        return 'weak performance';
    }
}
