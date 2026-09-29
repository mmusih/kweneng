<?php

namespace App\Services;

use App\Models\Mark;
use App\Models\StudentSubject;
use App\Models\TeacherSubject;
use Illuminate\Support\Collection;

class MarksMonitoringService
{
    /**
     * Build marks-entry progress from the same learner assignments used by the
     * teacher marks screen. This avoids treating electives, former learners, or
     * another teacher's group as missing work.
     */
    public function build(
        int $academicYearId,
        int $termId,
        string $assessment,
        array $filters = []
    ): array {
        $assessment = $assessment === 'midterm' ? 'midterm' : 'endterm';
        $scoreColumn = $assessment.'_score';
        $search = strtolower(trim((string) ($filters['search'] ?? '')));

        $teacherSubjects = TeacherSubject::with(['teacher.user', 'subject', 'class', 'academicYear'])
            ->where('academic_year_id', $academicYearId)
            ->when($filters['class_id'] ?? null, fn ($query, $id) => $query->where('class_id', $id))
            ->when($filters['subject_id'] ?? null, fn ($query, $id) => $query->where('subject_id', $id))
            ->when($filters['teacher_id'] ?? null, fn ($query, $id) => $query->where('teacher_id', $id))
            ->get()
            ->filter(function (TeacherSubject $assignment) use ($search) {
                if ($search === '') {
                    return true;
                }

                return str_contains(strtolower($assignment->teacher?->user?->name ?? ''), $search)
                    || str_contains(strtolower($assignment->subject?->name ?? ''), $search)
                    || str_contains(strtolower($assignment->class?->name ?? ''), $search);
            })
            ->groupBy('teacher_id');

        $teachersData = [];
        $totalExpected = 0;
        $totalCompleted = 0;
        $totalMissing = 0;

        foreach ($teacherSubjects as $groupTeacherId => $assignments) {
            $teacherName = $assignments->first()?->teacher?->user?->name ?? 'N/A';
            $subjectRows = [];
            $teacherExpected = 0;
            $teacherCompleted = 0;
            $teacherMissing = 0;

            foreach ($assignments as $assignment) {
                $studentSubjects = $this->activeStudentAssignments($assignment);
                $studentIds = $studentSubjects->pluck('student_id');
                $expected = $studentIds->count();

                // Marks are unique per learner, subject, year, and term. Do not
                // discard a valid mark merely because the responsible teacher
                // was reassigned after it was entered.
                $marks = Mark::query()
                    ->where('class_id', $assignment->class_id)
                    ->where('subject_id', $assignment->subject_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->where('term_id', $termId)
                    ->whereIn('student_id', $studentIds)
                    ->get()
                    ->keyBy('student_id');

                $completed = 0;
                $missingStudentNames = [];

                foreach ($studentSubjects as $studentSubject) {
                    $mark = $marks->get($studentSubject->student_id);

                    if ($mark && $mark->{$scoreColumn} !== null) {
                        $completed++;
                    } else {
                        $missingStudentNames[] = $studentSubject->student?->user?->name ?? 'Unknown Student';
                    }
                }

                $missing = max($expected - $completed, 0);
                $progress = $expected > 0 ? (int) round(($completed / $expected) * 100) : null;

                $subjectRows[] = [
                    'teacher' => $teacherName,
                    'class' => $assignment->class?->name ?? 'N/A',
                    'subject' => $assignment->subject?->name ?? 'N/A',
                    'expected' => $expected,
                    'completed' => $completed,
                    'missing' => $missing,
                    'progress' => $progress,
                    'status' => $this->statusFromProgress($progress),
                    'class_id' => $assignment->class_id,
                    'subject_id' => $assignment->subject_id,
                    'teacher_id' => $assignment->teacher_id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'term_id' => $termId,
                    'assessment' => $assessment,
                    'missing_student_names' => $missingStudentNames,
                ];

                $teacherExpected += $expected;
                $teacherCompleted += $completed;
                $teacherMissing += $missing;
            }

            usort($subjectRows, fn ($a, $b) => $this->compareRows($a, $b));

            $teacherProgress = $teacherExpected > 0
                ? (int) round(($teacherCompleted / $teacherExpected) * 100)
                : null;

            $teachersData[] = [
                'teacher' => $teacherName,
                'teacher_id' => (int) $groupTeacherId,
                'expected' => $teacherExpected,
                'completed' => $teacherCompleted,
                'missing' => $teacherMissing,
                'progress' => $teacherProgress,
                'status' => $this->statusFromProgress($teacherProgress),
                'subjects' => $subjectRows,
            ];

            $totalExpected += $teacherExpected;
            $totalCompleted += $teacherCompleted;
            $totalMissing += $teacherMissing;
        }

        usort($teachersData, fn ($a, $b) => $this->compareRows($a, $b));

        $overallProgress = $totalExpected > 0
            ? (int) round(($totalCompleted / $totalExpected) * 100)
            : null;
        $allSubjects = collect($teachersData)->flatMap(fn ($teacher) => $teacher['subjects']);
        $accountableTeachers = collect($teachersData)->filter(fn ($teacher) => $teacher['expected'] > 0);

        return [
            'teachersData' => $teachersData,
            'summary' => [
                'teachers' => $accountableTeachers->count(),
                'complete_teachers' => $accountableTeachers->where('missing', 0)->count(),
                'incomplete_teachers' => $accountableTeachers->where('missing', '>', 0)->count(),
                'assignments' => $allSubjects->where('expected', '>', 0)->count(),
                'complete_assignments' => $allSubjects
                    ->filter(fn ($row) => $row['expected'] > 0 && $row['missing'] === 0)
                    ->count(),
                'expected' => $totalExpected,
                'completed' => $totalCompleted,
                'missing' => $totalMissing,
                'progress' => $overallProgress,
                'critical_teachers' => $accountableTeachers
                    ->filter(fn ($teacher) => $teacher['progress'] !== null && $teacher['progress'] < 50)
                    ->count(),
            ],
        ];
    }

    public function defaultAssessment(int $academicYearId, int $termId): string
    {
        return Mark::query()
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->whereNotNull('endterm_score')
            ->exists()
                ? 'endterm'
                : 'midterm';
    }

    private function activeStudentAssignments(TeacherSubject $assignment): Collection
    {
        return StudentSubject::with('student.user')
            ->where('teacher_id', $assignment->teacher_id)
            ->where('class_id', $assignment->class_id)
            ->where('subject_id', $assignment->subject_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->whereHas('student.classHistory', function ($query) use ($assignment) {
                $query->where('class_id', $assignment->class_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->where('status', 'active')
                    ->whereNull('exited_at');
            })
            ->orderBy('student_id')
            ->get()
            ->unique('student_id')
            ->values();
    }

    private function statusFromProgress(?int $progress): string
    {
        if ($progress === null) {
            return 'not_required';
        }

        return match (true) {
            $progress >= 100 => 'complete',
            $progress >= 80 => 'good',
            $progress >= 50 => 'pending',
            default => 'critical',
        };
    }

    private function compareRows(array $a, array $b): int
    {
        $aProgress = $a['progress'] ?? 101;
        $bProgress = $b['progress'] ?? 101;

        return $aProgress === $bProgress
            ? $b['missing'] <=> $a['missing']
            : $aProgress <=> $bProgress;
    }
}
