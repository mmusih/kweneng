<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Mark;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Term;
use Illuminate\Http\Request;

class MarksMonitorController extends Controller
{
    public function index(Request $request)
    {
        $academicYearId = $request->input('academic_year_id');
        $termId = $request->input('term_id');
        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');
        $teacherId = $request->input('teacher_id');
        $assessment = $request->input('assessment', 'endterm'); // midterm | endterm
        $search = trim((string) $request->input('search', ''));

        $academicYears = AcademicYear::orderByDesc('id')->get();
        $classes = ClassModel::orderBy('level')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $teachers = Teacher::with('user')->get();
        $terms = collect();

        if ($academicYearId) {
            $terms = Term::where('academic_year_id', $academicYearId)
                ->orderBy('start_date')
                ->get();
        }

        $teacherSubjects = TeacherSubject::with(['teacher.user', 'subject', 'class', 'academicYear'])
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->when($classId, fn($q) => $q->where('class_id', $classId))
            ->when($subjectId, fn($q) => $q->where('subject_id', $subjectId))
            ->when($teacherId, fn($q) => $q->where('teacher_id', $teacherId))
            ->get()
            ->filter(function ($row) use ($search) {
                if ($search === '') {
                    return true;
                }

                $teacherName = strtolower($row->teacher?->user?->name ?? '');
                $subjectName = strtolower($row->subject?->name ?? '');
                $className = strtolower($row->class?->name ?? '');

                return str_contains($teacherName, strtolower($search))
                    || str_contains($subjectName, strtolower($search))
                    || str_contains($className, strtolower($search));
            })
            ->groupBy('teacher_id');

        $teachersData = [];
        $totalExpected = 0;
        $totalCompleted = 0;
        $totalMissing = 0;

        foreach ($teacherSubjects as $groupTeacherId => $assignments) {
            $teacherName = optional($assignments->first()->teacher?->user)->name ?? 'N/A';

            $subjectRows = [];
            $teacherExpected = 0;
            $teacherCompleted = 0;
            $teacherMissing = 0;

            foreach ($assignments as $assignment) {
                $studentSubjects = StudentSubject::with('student.user')
                    ->where('class_id', $assignment->class_id)
                    ->where('subject_id', $assignment->subject_id)
                    ->where('academic_year_id', $assignment->academic_year_id)
                    ->get();

                $expected = $studentSubjects->count();

                $marks = collect();
                if ($termId) {
                    $marks = Mark::where('class_id', $assignment->class_id)
                        ->where('subject_id', $assignment->subject_id)
                        ->where('teacher_id', $assignment->teacher_id)
                        ->where('academic_year_id', $assignment->academic_year_id)
                        ->where('term_id', $termId)
                        ->get()
                        ->keyBy('student_id');
                }

                $completed = 0;
                $missingStudentNames = [];

                foreach ($studentSubjects as $studentSubject) {
                    $mark = $marks->get($studentSubject->student_id);

                    $hasValue = false;
                    if ($assessment === 'midterm') {
                        $hasValue = $mark && $mark->midterm_score !== null;
                    } else {
                        $hasValue = $mark && $mark->endterm_score !== null;
                    }

                    if ($hasValue) {
                        $completed++;
                    } else {
                        $missingStudentNames[] = $studentSubject->student?->user?->name ?? 'Unknown Student';
                    }
                }

                $missing = max($expected - $completed, 0);
                $progress = $expected > 0 ? (int) round(($completed / $expected) * 100) : 0;

                $status = 'critical';
                if ($progress >= 100) {
                    $status = 'complete';
                } elseif ($progress >= 80) {
                    $status = 'good';
                } elseif ($progress >= 50) {
                    $status = 'pending';
                }

                $subjectRows[] = [
                    'teacher' => $teacherName,
                    'class' => $assignment->class?->name ?? 'N/A',
                    'subject' => $assignment->subject?->name ?? 'N/A',
                    'expected' => $expected,
                    'completed' => $completed,
                    'missing' => $missing,
                    'progress' => $progress,
                    'status' => $status,
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

            usort($subjectRows, function ($a, $b) {
                if ($a['progress'] === $b['progress']) {
                    return $b['missing'] <=> $a['missing'];
                }
                return $a['progress'] <=> $b['progress'];
            });

            $teacherProgress = $teacherExpected > 0
                ? (int) round(($teacherCompleted / $teacherExpected) * 100)
                : 0;

            $teachersData[] = [
                'teacher' => $teacherName,
                'teacher_id' => $groupTeacherId,
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

        usort($teachersData, function ($a, $b) {
            if ($a['progress'] === $b['progress']) {
                return $b['missing'] <=> $a['missing'];
            }
            return $a['progress'] <=> $b['progress'];
        });

        $overallProgress = $totalExpected > 0
            ? (int) round(($totalCompleted / $totalExpected) * 100)
            : 0;

        $summary = [
            'teachers' => count($teachersData),
            'assignments' => collect($teachersData)->sum(fn($t) => count($t['subjects'])),
            'expected' => $totalExpected,
            'completed' => $totalCompleted,
            'missing' => $totalMissing,
            'progress' => $overallProgress,
            'critical_teachers' => collect($teachersData)->filter(fn($t) => $t['progress'] < 50)->count(),
        ];

        return view('headmaster.marks.index', compact(
            'teachersData',
            'summary',
            'academicYears',
            'classes',
            'subjects',
            'teachers',
            'terms',
            'assessment',
            'academicYearId',
            'termId',
            'classId',
            'subjectId',
            'teacherId',
            'search'
        ));
    }

    public function show(Request $request)
    {
        $classId = $request->input('class_id');
        $subjectId = $request->input('subject_id');
        $teacherId = $request->input('teacher_id');
        $academicYearId = $request->input('academic_year_id');
        $termId = $request->input('term_id');
        $assessment = $request->input('assessment', 'endterm');

        $studentSubjects = StudentSubject::with([
            'student.user',
            'class',
            'subject',
            'teacher.user',
            'academicYear',
        ])
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('academic_year_id', $academicYearId)
            ->get();

        $marks = Mark::where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->get()
            ->keyBy('student_id');

        $detailRows = $studentSubjects->map(function ($studentSubject) use ($marks, $assessment) {
            $mark = $marks->get($studentSubject->student_id);

            $value = $assessment === 'midterm'
                ? $mark?->midterm_score
                : $mark?->endterm_score;

            return [
                'student_name' => $studentSubject->student?->user?->name ?? 'N/A',
                'admission_no' => $studentSubject->student?->admission_no ?? 'N/A',
                'value' => $value,
                'missing' => $value === null,
            ];
        })->sortByDesc('missing')->values();

        $meta = [
            'class' => optional($studentSubjects->first()?->class)->name ?? 'N/A',
            'subject' => optional($studentSubjects->first()?->subject)->name ?? 'N/A',
            'teacher' => optional($studentSubjects->first()?->teacher?->user)->name ?? 'N/A',
            'assessment' => ucfirst($assessment),
        ];

        return view('headmaster.marks.show', compact('detailRows', 'meta'));
    }

    protected function statusFromProgress(int $progress): string
    {
        if ($progress >= 100) {
            return 'complete';
        }

        if ($progress >= 80) {
            return 'good';
        }

        if ($progress >= 50) {
            return 'pending';
        }

        return 'critical';
    }
}
