<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\ClassModel;
use App\Models\HeadmasterComment;
use App\Models\Mark;
use App\Models\Punctuality;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $activeAcademicYear = AcademicYear::where('active', true)->first();

        $currentTerm = null;
        $dashboard = [
            'schoolAverage' => null,
            'midtermAverage' => null,
            'endtermAverage' => null,
            'bestClass' => null,
            'weakestClass' => null,
            'topSubject' => null,
            'weakestSubject' => null,
            'atRiskStudentsCount' => 0,
            'totalMarks' => 0,
            'averageMarksCompletion' => null,
            'classesFullySubmitted' => 0,
            'classesPendingSubmission' => 0,
            'attendanceRate' => null,
            'punctualityOnTimeRate' => null,
            'behaviourIncidentCount' => 0,
            'majorBehaviourCount' => 0,
            'studentsWithComments' => 0,
            'studentsWithoutComments' => 0,
            'recentAtRiskStudents' => collect(),
        ];

        $stats = [
            'totalStudents' => Student::count(),
            'totalClasses' => ClassModel::count(),
            'totalSubjects' => Subject::count(),
            'totalComments' => HeadmasterComment::count(),
        ];

        if ($activeAcademicYear) {
            $currentTerm = Term::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', 'active')
                ->orderBy('start_date')
                ->first();

            $marksQuery = Mark::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id));

            $marks = $marksQuery->get();
            $dashboard['totalMarks'] = $marks->count();

            $midtermScores = $marks->pluck('midterm_score')->filter(fn($v) => $v !== null);
            $endtermScores = $marks->pluck('endterm_score')->filter(fn($v) => $v !== null);

            $dashboard['midtermAverage'] = $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null;
            $dashboard['endtermAverage'] = $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null;

            $allAverages = $marks->map(function ($mark) {
                if ($mark->midterm_score !== null && $mark->endterm_score !== null) {
                    return ($mark->midterm_score + $mark->endterm_score) / 2;
                }
                if ($mark->midterm_score !== null) {
                    return $mark->midterm_score;
                }
                if ($mark->endterm_score !== null) {
                    return $mark->endterm_score;
                }
                return null;
            })->filter(fn($v) => $v !== null);

            $dashboard['schoolAverage'] = $allAverages->isNotEmpty() ? round($allAverages->avg(), 2) : null;

            $classPerformance = ClassModel::query()
                ->leftJoin('marks', function ($join) use ($activeAcademicYear, $currentTerm) {
                    $join->on('classes.id', '=', 'marks.class_id')
                        ->where('marks.academic_year_id', '=', $activeAcademicYear->id);

                    if ($currentTerm) {
                        $join->where('marks.term_id', '=', $currentTerm->id);
                    }
                })
                ->select(
                    'classes.id',
                    'classes.name',
                    DB::raw('AVG(
                        CASE
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN (marks.midterm_score + marks.endterm_score) / 2
                            WHEN marks.midterm_score IS NOT NULL THEN marks.midterm_score
                            WHEN marks.endterm_score IS NOT NULL THEN marks.endterm_score
                            ELSE NULL
                        END
                    ) as average_score')
                )
                ->groupBy('classes.id', 'classes.name')
                ->orderByDesc('average_score')
                ->get()
                ->filter(fn($row) => $row->average_score !== null)
                ->values();

            $dashboard['bestClass'] = $classPerformance->first();
            $dashboard['weakestClass'] = $classPerformance->last();

            $subjectPerformance = Subject::query()
                ->leftJoin('marks', function ($join) use ($activeAcademicYear, $currentTerm) {
                    $join->on('subjects.id', '=', 'marks.subject_id')
                        ->where('marks.academic_year_id', '=', $activeAcademicYear->id);

                    if ($currentTerm) {
                        $join->where('marks.term_id', '=', $currentTerm->id);
                    }
                })
                ->select(
                    'subjects.id',
                    'subjects.name',
                    DB::raw('AVG(
                        CASE
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN (marks.midterm_score + marks.endterm_score) / 2
                            WHEN marks.midterm_score IS NOT NULL THEN marks.midterm_score
                            WHEN marks.endterm_score IS NOT NULL THEN marks.endterm_score
                            ELSE NULL
                        END
                    ) as average_score')
                )
                ->groupBy('subjects.id', 'subjects.name')
                ->orderByDesc('average_score')
                ->get()
                ->filter(fn($row) => $row->average_score !== null)
                ->values();

            $dashboard['topSubject'] = $subjectPerformance->first();
            $dashboard['weakestSubject'] = $subjectPerformance->last();

            $studentAverages = Student::query()
                ->join('marks', 'students.id', '=', 'marks.student_id')
                ->where('marks.academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn($q) => $q->where('marks.term_id', $currentTerm->id))
                ->select(
                    'students.id',
                    DB::raw('AVG(
                        CASE
                            WHEN marks.midterm_score IS NOT NULL AND marks.endterm_score IS NOT NULL THEN (marks.midterm_score + marks.endterm_score) / 2
                            WHEN marks.midterm_score IS NOT NULL THEN marks.midterm_score
                            WHEN marks.endterm_score IS NOT NULL THEN marks.endterm_score
                            ELSE NULL
                        END
                    ) as average_score')
                )
                ->groupBy('students.id')
                ->get();

            $dashboard['atRiskStudentsCount'] = $studentAverages
                ->filter(fn($row) => $row->average_score !== null && $row->average_score < 50)
                ->count();

            $dashboard['recentAtRiskStudents'] = Student::with(['user', 'currentClass'])
                ->whereIn(
                    'id',
                    $studentAverages
                        ->filter(fn($row) => $row->average_score !== null && $row->average_score < 50)
                        ->sortBy('average_score')
                        ->take(8)
                        ->pluck('id')
                )
                ->get();

            $classes = ClassModel::withCount('students')
                ->with(['classSubjects' => function ($q) use ($activeAcademicYear) {
                    $q->where('academic_year_id', $activeAcademicYear->id);
                }])
                ->get();

            $completionRows = $classes->map(function ($class) use ($activeAcademicYear, $currentTerm) {
                $studentCount = $class->students_count;
                $subjectCount = $class->classSubjects->count();
                $expectedMarks = $studentCount * $subjectCount;

                $actualMarks = Mark::query()
                    ->where('class_id', $class->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
                    ->count();

                $completion = $expectedMarks > 0 ? round(($actualMarks / $expectedMarks) * 100, 1) : null;

                return [
                    'class_id' => $class->id,
                    'completion' => $completion,
                ];
            })->filter(fn($row) => $row['completion'] !== null)->values();

            $dashboard['averageMarksCompletion'] = $completionRows->count()
                ? round(collect($completionRows)->avg('completion'), 1)
                : null;

            $dashboard['classesFullySubmitted'] = collect($completionRows)
                ->filter(fn($row) => $row['completion'] >= 100)
                ->count();

            $dashboard['classesPendingSubmission'] = collect($completionRows)
                ->filter(fn($row) => $row['completion'] < 100)
                ->count();

            $attendanceRecords = Attendance::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
                ->get();

            $attendanceTotal = $attendanceRecords->count();
            $attendancePresentEquivalent = $attendanceRecords->whereIn('status', [
                Attendance::STATUS_PRESENT,
                Attendance::STATUS_LATE,
                Attendance::STATUS_EXCUSED,
            ])->count();

            $dashboard['attendanceRate'] = $attendanceTotal > 0
                ? round(($attendancePresentEquivalent / $attendanceTotal) * 100, 1)
                : null;

            $punctualityRecords = Punctuality::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
                ->get();

            $punctualityTotal = $punctualityRecords->count();
            $dashboard['punctualityOnTimeRate'] = $punctualityTotal > 0
                ? round(($punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count() / $punctualityTotal) * 100, 1)
                : null;

            $behaviourRecords = BehaviourRecord::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn($q) => $q->where('term_id', $currentTerm->id))
                ->get();

            $dashboard['behaviourIncidentCount'] = $behaviourRecords->count();
            $dashboard['majorBehaviourCount'] = $behaviourRecords
                ->where('severity', BehaviourRecord::SEVERITY_MAJOR)
                ->count();

            if ($currentTerm) {
                $dashboard['studentsWithComments'] = HeadmasterComment::where('term_id', $currentTerm->id)->count();

                $dashboard['studentsWithoutComments'] = Student::count() - $dashboard['studentsWithComments'];
                if ($dashboard['studentsWithoutComments'] < 0) {
                    $dashboard['studentsWithoutComments'] = 0;
                }
            }
        }

        return view('headmaster.dashboard', compact(
            'activeAcademicYear',
            'currentTerm',
            'dashboard',
            'stats'
        ));
    }
}
