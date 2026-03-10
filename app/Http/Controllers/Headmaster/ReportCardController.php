<?php

namespace App\Http\Controllers\Headmaster;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\HeadmasterComment;
use App\Models\Punctuality;
use App\Models\Student;
use App\Models\Term;
use App\Models\ClassModel;
use App\Services\ExamSummaryService;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function __construct(
        protected ExamSummaryService $examSummaryService
    ) {}

    public function index(Request $request)
    {
        $activeAcademicYear = AcademicYear::where('active', true)->first();

        $classes = collect();
        $terms = collect();
        $students = collect();

        $selectedClassId = $request->input('class_id');
        $selectedTermId = $request->input('term_id');

        if ($activeAcademicYear) {
            $classes = ClassModel::where('academic_year_id', $activeAcademicYear->id)
                ->orderBy('level')
                ->orderBy('name')
                ->get();

            $terms = Term::where('academic_year_id', $activeAcademicYear->id)
                ->orderBy('start_date')
                ->get();

            if ($selectedClassId && $selectedTermId) {
                $midtermSummary = $this->examSummaryService->generate(
                    (int) $selectedClassId,
                    (int) $activeAcademicYear->id,
                    (int) $selectedTermId,
                    ExamSummaryService::EXAM_MIDTERM
                );

                $endtermSummary = $this->examSummaryService->generate(
                    (int) $selectedClassId,
                    (int) $activeAcademicYear->id,
                    (int) $selectedTermId,
                    ExamSummaryService::EXAM_ENDTERM
                );

                $midtermRows = collect($midtermSummary['rows'])->keyBy('student_id');
                $endtermRows = collect($endtermSummary['rows'])->keyBy('student_id');

                $students = Student::with('user', 'currentClass')
                    ->where('current_class_id', $selectedClassId)
                    ->orderBy('admission_no')
                    ->get()
                    ->map(function ($student) use ($midtermRows, $endtermRows) {
                        $mid = $midtermRows->get($student->id);
                        $end = $endtermRows->get($student->id);

                        $student->midterm_average = $mid['average'] ?? null;
                        $student->midterm_position = $mid['position'] ?? null;
                        $student->endterm_average = $end['average'] ?? null;
                        $student->endterm_position = $end['position'] ?? null;

                        return $student;
                    });
            }
        }

        return view('headmaster.reports.index', compact(
            'activeAcademicYear',
            'classes',
            'terms',
            'students',
            'selectedClassId',
            'selectedTermId'
        ));
    }

    public function show(Request $request, Student $student)
    {
        $termId = $request->input('term_id');

        abort_unless($termId, 404, 'Term is required.');

        $term = Term::findOrFail($termId);
        $academicYear = AcademicYear::findOrFail($term->academic_year_id);

        $student->load(['user', 'currentClass']);

        $marks = $student->marks()
            ->with('subject')
            ->where('academic_year_id', $academicYear->id)
            ->where('term_id', $term->id)
            ->get();

        $subjects = $marks->map(function ($mark) {
            $midterm = $mark->midterm_score;
            $endterm = $mark->endterm_score;

            $average = null;
            if ($midterm !== null && $endterm !== null) {
                $average = ($midterm + $endterm) / 2;
            } elseif ($midterm !== null) {
                $average = $midterm;
            } elseif ($endterm !== null) {
                $average = $endterm;
            }

            return [
                'subject_name' => $mark->subject->name ?? 'Unknown Subject',
                'subject_code' => $mark->subject->code ?? null,
                'midterm_score' => $midterm,
                'endterm_score' => $endterm,
                'average' => $average,
                'grade' => $mark->grade,
                'remarks' => $mark->remarks,
            ];
        });

        $averages = $subjects->pluck('average')->filter(fn($value) => $value !== null);
        $overallAverage = $averages->count() ? round($averages->avg(), 2) : null;

        $midtermScores = $subjects->pluck('midterm_score')->filter(fn($value) => $value !== null);
        $endtermScores = $subjects->pluck('endterm_score')->filter(fn($value) => $value !== null);

        $midtermAverage = $midtermScores->count() ? round($midtermScores->avg(), 2) : null;
        $midtermTotal = $midtermScores->count() ? round($midtermScores->sum(), 2) : null;

        $endtermAverage = $endtermScores->count() ? round($endtermScores->avg(), 2) : null;
        $endtermTotal = $endtermScores->count() ? round($endtermScores->sum(), 2) : null;

        $midtermRanking = $this->examSummaryService->getStudentPosition(
            $student->id,
            $student->current_class_id,
            $academicYear->id,
            $term->id,
            ExamSummaryService::EXAM_MIDTERM
        );

        $endtermRanking = $this->examSummaryService->getStudentPosition(
            $student->id,
            $student->current_class_id,
            $academicYear->id,
            $term->id,
            ExamSummaryService::EXAM_ENDTERM
        );

        $attendanceRecords = Attendance::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('term_id', $term->id)
            ->get();

        $attendanceTotal = $attendanceRecords->count();
        $attendancePresentEquivalent = $attendanceRecords->whereIn('status', [
            Attendance::STATUS_PRESENT,
            Attendance::STATUS_LATE,
            Attendance::STATUS_EXCUSED,
        ])->count();

        $attendanceSummary = [
            'present' => $attendanceRecords->where('status', Attendance::STATUS_PRESENT)->count(),
            'absent' => $attendanceRecords->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $attendanceRecords->where('status', Attendance::STATUS_LATE)->count(),
            'excused' => $attendanceRecords->where('status', Attendance::STATUS_EXCUSED)->count(),
            'rate' => $attendanceTotal > 0 ? round(($attendancePresentEquivalent / $attendanceTotal) * 100, 1) : null,
        ];

        $punctualityRecords = Punctuality::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('term_id', $term->id)
            ->get();

        $punctualityTotal = $punctualityRecords->count();
        $punctualitySummary = [
            'on_time' => $punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count(),
            'late' => $punctualityRecords->where('status', Punctuality::STATUS_LATE)->count(),
            'very_late' => $punctualityRecords->where('status', Punctuality::STATUS_VERY_LATE)->count(),
            'absent' => $punctualityRecords->where('status', Punctuality::STATUS_ABSENT)->count(),
            'on_time_rate' => $punctualityTotal > 0
                ? round(($punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count() / $punctualityTotal) * 100, 1)
                : null,
        ];

        $behaviourRecords = BehaviourRecord::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('term_id', $term->id)
            ->latest('record_date')
            ->latest('id')
            ->get();

        $behaviourSummary = [
            'total' => $behaviourRecords->count(),
            'minor' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MINOR)->count(),
            'moderate' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MODERATE)->count(),
            'major' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MAJOR)->count(),
            'recent' => $behaviourRecords->take(5),
        ];

        $headmasterComment = HeadmasterComment::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->first();

        return view('headmaster.reports.show', compact(
            'student',
            'term',
            'academicYear',
            'subjects',
            'overallAverage',
            'midtermAverage',
            'midtermTotal',
            'midtermRanking',
            'endtermAverage',
            'endtermTotal',
            'endtermRanking',
            'attendanceSummary',
            'punctualitySummary',
            'behaviourSummary',
            'headmasterComment'
        ));
    }
}
