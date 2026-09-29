<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AwardRun;
use App\Models\BehaviourRecord;
use App\Models\ClassModel;
use App\Models\HeadmasterComment;
use App\Models\Mark;
use App\Models\Punctuality;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\MarksMonitoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly MarksMonitoringService $marksMonitoring) {}

    public function index(Request $request)
    {
        $request->validate(['term_id' => ['nullable', 'integer', 'exists:terms,id']]);
        $terms = Term::with('academicYear')->orderByDesc('start_date')->get();
        $selectedTerm = $request->filled('term_id') ? $terms->firstWhere('id', (int) $request->term_id) : null;
        $activeAcademicYear = AcademicYear::where('active', true)->first();
        if ($selectedTerm) {
            $activeAcademicYear = $selectedTerm->academicYear;
        }
        $classStats = collect();

        $awardRuns = AwardRun::query()
            ->when($activeAcademicYear, fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id));
        $awardOverview = [
            'drafts' => (clone $awardRuns)->where('status', 'draft')->count(),
            'published' => (clone $awardRuns)->where('status', 'published')->count(),
            'recipients' => (clone $awardRuns)->withCount('awards')->get()->sum('awards_count'),
            'recent' => (clone $awardRuns)->with(['academicYear', 'term'])->withCount('awards')->latest()->limit(4)->get(),
        ];

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
            'marksAssessment' => 'midterm',
            'marksExpected' => 0,
            'marksEntered' => 0,
            'marksMissing' => 0,
            'teachersFullySubmitted' => 0,
            'teachersPendingSubmission' => 0,
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

        $currentTerm = $selectedTerm ?? ($activeAcademicYear ? Term::current($activeAcademicYear->id) : null);
        if ($activeAcademicYear && $currentTerm) {

            $marksQuery = Mark::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn ($q) => $q->where('term_id', $currentTerm->id));

            $marks = $marksQuery->get();
            $classStats = ClassModel::where('academic_year_id', $activeAcademicYear->id)->orderBy('name')->get()->map(function ($class) use ($marks) {
                $rows = $marks->where('class_id', $class->id)->groupBy('student_id')->map(function ($rows) {
                    return $rows->map(fn ($mark) => collect([$mark->midterm_score, $mark->endterm_score])->filter(fn ($score) => $score !== null)->avg())->filter(fn ($score) => $score !== null)->avg();
                })->filter(fn ($score) => $score !== null);
                return ['id' => $class->id, 'name' => $class->name, 'assessed' => $rows->count(), 'average' => $rows->avg(), 'attention' => $rows->filter(fn ($score) => $score < 50)->count(), 'lowest' => $rows->min(), 'highest' => $rows->max()];
            });
            $dashboard['totalMarks'] = $marks->count();

            $midtermScores = $marks->pluck('midterm_score')->filter(fn ($v) => $v !== null);
            $endtermScores = $marks->pluck('endterm_score')->filter(fn ($v) => $v !== null);

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
            })->filter(fn ($v) => $v !== null);

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
                ->filter(fn ($row) => $row->average_score !== null)
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
                ->filter(fn ($row) => $row->average_score !== null)
                ->values();

            $dashboard['topSubject'] = $subjectPerformance->first();
            $dashboard['weakestSubject'] = $subjectPerformance->last();

            $studentAverages = Student::query()
                ->join('marks', 'students.id', '=', 'marks.student_id')
                ->where('marks.academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn ($q) => $q->where('marks.term_id', $currentTerm->id))
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
                ->filter(fn ($row) => $row->average_score !== null && $row->average_score < 50)
                ->count();

            $dashboard['recentAtRiskStudents'] = Student::with(['user', 'currentClass'])
                ->whereIn(
                    'id',
                    $studentAverages
                        ->filter(fn ($row) => $row->average_score !== null && $row->average_score < 50)
                        ->sortBy('average_score')
                        ->pluck('id')
                )
                ->get()->each(function ($student) use ($studentAverages, $marks, $classStats) {
                    $student->average_score = $studentAverages->firstWhere('id', $student->id)->average_score;
                    $student->term_class_names = $classStats->whereIn('id', $marks->where('student_id', $student->id)->pluck('class_id'))->pluck('name')->join(', ');
                })->sortBy('average_score')->values();

            if ($currentTerm) {
                $dashboard['marksAssessment'] = $this->marksMonitoring->defaultAssessment(
                    $activeAcademicYear->id,
                    $currentTerm->id
                );
                $marksMonitor = $this->marksMonitoring->build(
                    $activeAcademicYear->id,
                    $currentTerm->id,
                    $dashboard['marksAssessment']
                );
                $completionSummary = $marksMonitor['summary'];

                $dashboard['averageMarksCompletion'] = $completionSummary['progress'];
                $dashboard['marksExpected'] = $completionSummary['expected'];
                $dashboard['marksEntered'] = $completionSummary['completed'];
                $dashboard['marksMissing'] = $completionSummary['missing'];
                $dashboard['teachersFullySubmitted'] = $completionSummary['complete_teachers'];
                $dashboard['teachersPendingSubmission'] = $completionSummary['incomplete_teachers'];
            }

            $attendanceRecords = Attendance::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn ($q) => $q->where('term_id', $currentTerm->id))
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
                ->when($currentTerm, fn ($q) => $q->where('term_id', $currentTerm->id))
                ->get();

            $punctualityTotal = $punctualityRecords->count();
            $dashboard['punctualityOnTimeRate'] = $punctualityTotal > 0
                ? round(($punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count() / $punctualityTotal) * 100, 1)
                : null;

            $behaviourRecords = BehaviourRecord::query()
                ->where('academic_year_id', $activeAcademicYear->id)
                ->when($currentTerm, fn ($q) => $q->where('term_id', $currentTerm->id))
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
            'stats',
            'awardOverview',
            'terms',
            'classStats'
        ));
    }
}
