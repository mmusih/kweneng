<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Support\Collection;

class ExamSummaryService
{
    public const EXAM_MIDTERM = 'midterm';
    public const EXAM_ENDTERM = 'endterm';

    public function __construct(
        protected MarksService $marksService
    ) {}

    public static function examTypes(): array
    {
        return [
            self::EXAM_MIDTERM,
            self::EXAM_ENDTERM,
        ];
    }

    public function generate(int $classId, int $academicYearId, int $termId, string $examType): array
    {
        if (!in_array($examType, self::examTypes(), true)) {
            abort(422, 'Invalid exam type.');
        }

        $class = ClassModel::with(['academicYear', 'classTeacher.user'])->findOrFail($classId);

        $students = Student::with('user')
            ->where('current_class_id', $classId)
            ->orderBy('admission_no')
            ->get();

        $subjectIds = StudentSubject::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('subject_id')
            ->unique()
            ->values();

        $subjects = Subject::whereIn('id', $subjectIds)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code ?: strtoupper(substr($subject->name, 0, 4)),
                ];
            })
            ->values();

        $studentSubjectMap = StudentSubject::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->groupBy('student_id')
            ->map(function ($rows) {
                return $rows->pluck('subject_id')->values()->all();
            });

        $marks = Mark::where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function ($student) use ($subjects, $studentSubjectMap, $marks, $examType) {
            $studentMarks = $marks->get($student->id, collect())->keyBy('subject_id');
            $studentSubjectIds = collect($studentSubjectMap->get($student->id, []));

            $subjectScores = [];
            $scoreValues = [];

            foreach ($subjects as $subject) {
                if (!$studentSubjectIds->contains($subject['id'])) {
                    $subjectScores[$subject['id']] = [
                        'score' => null,
                        'grade' => null,
                        'display' => '',
                    ];
                    continue;
                }

                $mark = $studentMarks->get($subject['id']);

                $score = null;
                if ($mark) {
                    $score = $examType === self::EXAM_MIDTERM
                        ? $mark->midterm_score
                        : $mark->endterm_score;
                }

                $grade = $score !== null ? $this->marksService->calculateGrade($score) : null;

                $subjectScores[$subject['id']] = [
                    'score' => $score,
                    'grade' => $grade,
                    'display' => $score !== null
                        ? $this->formatScoreWithGrade($score, $grade)
                        : '',
                ];

                if ($score !== null) {
                    $scoreValues[] = (float) $score;
                }
            }

            $total = count($scoreValues) > 0 ? array_sum($scoreValues) : null;
            $average = count($scoreValues) > 0 ? round($total / count($scoreValues), 2) : null;

            return [
                'student_id' => $student->id,
                'student_name' => $student->user->name ?? 'Unknown Student',
                'scores' => $subjectScores,
                'total' => $total !== null ? round($total, 2) : null,
                'average' => $average,
                'grade' => $average !== null ? $this->marksService->calculateGrade($average) : null,
                'position' => null,
            ];
        })->values();

        $ranked = $this->applyPositions($rows);

        $subjectAverages = $this->calculateSubjectAverages($subjects, $ranked);
        $classAverage = collect($subjectAverages)
            ->pluck('average')
            ->filter(fn($value) => $value !== null)
            ->avg();

        return [
            'class' => $class,
            'class_teacher_name' => $class->classTeacher?->user?->name ?? 'Not Assigned',
            'subjects' => $subjects,
            'rows' => $ranked,
            'exam_type' => $examType,
            'subject_averages' => $subjectAverages,
            'class_average' => $classAverage !== null ? round($classAverage, 2) : null,
            'grade_scale' => $this->gradeScale(),
        ];
    }

    protected function applyPositions(Collection $rows): Collection
    {
        $sorted = $rows
            ->sortByDesc(function ($row) {
                return $row['average'] ?? -1;
            })
            ->values()
            ->toArray();

        $currentPosition = 0;
        $lastAverage = null;

        foreach ($sorted as $index => $row) {
            if ($row['average'] === null) {
                $sorted[$index]['position'] = null;
                continue;
            }

            if ($lastAverage === null || (float) $row['average'] !== (float) $lastAverage) {
                $currentPosition = $index + 1;
                $lastAverage = $row['average'];
            }

            $sorted[$index]['position'] = $currentPosition;
        }

        return collect($sorted)->values();
    }

    protected function calculateSubjectAverages(Collection $subjects, Collection $rows): Collection
    {
        return $subjects->map(function ($subject) use ($rows) {
            $scores = $rows->map(function ($row) use ($subject) {
                return $row['scores'][$subject['id']]['score'] ?? null;
            })->filter(fn($value) => $value !== null)->values();

            $average = $scores->count() > 0 ? round($scores->avg(), 2) : null;
            $grade = $average !== null ? $this->marksService->calculateGrade($average) : null;

            return [
                'subject_id' => $subject['id'],
                'subject_code' => $subject['code'],
                'average' => $average,
                'grade' => $grade,
                'display' => $average !== null
                    ? $this->formatScoreWithGrade($average, $grade)
                    : '',
            ];
        })->values();
    }

    protected function formatScoreWithGrade(float|int $score, ?string $grade): string
    {
        $formatted = (floor($score) == $score)
            ? number_format($score, 0)
            : number_format($score, 2);

        return $grade ? $formatted . $grade : $formatted;
    }

    protected function gradeScale(): array
    {
        return [
            'A' => '80 - 100',
            'B' => '70 - 79',
            'C' => '60 - 69',
            'D' => '50 - 59',
            'E' => '40 - 49',
            'F' => '0 - 39',
        ];
    }

    public function getStudentPosition(
        int $studentId,
        int $classId,
        int $academicYearId,
        int $termId,
        string $examType
    ): ?array {
        $summary = $this->generate($classId, $academicYearId, $termId, $examType);

        $row = collect($summary['rows'])->firstWhere('student_id', $studentId);

        if (!$row) {
            return null;
        }

        return [
            'position' => $row['position'],
            'average' => $row['average'],
            'total' => $row['total'],
            'class_size' => collect($summary['rows'])->count(),
            'ranked_students_count' => collect($summary['rows'])->filter(fn($r) => $r['average'] !== null)->count(),
        ];
    }
}
