<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\Mark;
use App\Models\Punctuality;
use App\Models\Term;
use App\Services\StudentPerformanceService;

class DashboardController extends Controller
{
    public function __construct(
        protected StudentPerformanceService $studentPerformanceService
    ) {}

    public function index()
    {
        $parent = auth()->user()->parent;

        $children = collect();

        if ($parent) {
            $children = $parent->students()
                ->with(['user', 'currentClass.academicYear'])
                ->get();
        }

        $accessibleChildren = $children->filter(function ($child) {
            return !(bool) $child->fees_blocked;
        })->values();

        $blockedChildren = $children->filter(function ($child) {
            return (bool) $child->fees_blocked;
        })->values();

        $currentAcademicYear = AcademicYear::where('active', true)->first();

        $currentTerm = null;
        if ($currentAcademicYear) {
            $currentTerm = Term::where('academic_year_id', $currentAcademicYear->id)
                ->where('locked', false)
                ->orderBy('start_date', 'asc')
                ->first();
        }

        $marksOverview = collect();

        if ($currentTerm && $accessibleChildren->isNotEmpty()) {
            $studentIds = $accessibleChildren->pluck('id');

            $marksByStudent = Mark::whereIn('student_id', $studentIds)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('term_id', $currentTerm->id)
                ->with('subject')
                ->get()
                ->groupBy('student_id');

            $attendanceByStudent = Attendance::whereIn('student_id', $studentIds)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('term_id', $currentTerm->id)
                ->get()
                ->groupBy('student_id');

            $punctualityByStudent = Punctuality::whereIn('student_id', $studentIds)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('term_id', $currentTerm->id)
                ->get()
                ->groupBy('student_id');

            $behaviourByStudent = BehaviourRecord::whereIn('student_id', $studentIds)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('term_id', $currentTerm->id)
                ->latest('record_date')
                ->latest('id')
                ->get()
                ->groupBy('student_id');

            $marksOverview = $accessibleChildren->map(function ($child) use (
                $marksByStudent,
                $attendanceByStudent,
                $punctualityByStudent,
                $behaviourByStudent,
                $currentAcademicYear,
                $currentTerm
            ) {
                $marks = $marksByStudent->get($child->id, collect());
                $attendance = $attendanceByStudent->get($child->id, collect());
                $punctuality = $punctualityByStudent->get($child->id, collect());
                $behaviour = $behaviourByStudent->get($child->id, collect());

                $midtermScores = $marks->pluck('midterm_score')->filter(fn($score) => $score !== null);
                $endtermScores = $marks->pluck('endterm_score')->filter(fn($score) => $score !== null);

                $attendanceTotal = $attendance->count();
                $attendancePresentEquivalent = $attendance->whereIn('status', [
                    Attendance::STATUS_PRESENT,
                    Attendance::STATUS_LATE,
                    Attendance::STATUS_EXCUSED,
                ])->count();

                $performance = $this->studentPerformanceService
                    ->getStudentTermPerformance($child, $currentAcademicYear->id, $currentTerm->id);

                return [
                    'student_id' => $child->id,
                    'student_name' => $child->user->name ?? 'Unknown Student',
                    'admission_no' => $child->admission_no ?? 'N/A',
                    'class_name' => $child->currentClass->name ?? 'N/A',

                    'subjects_count' => $marks->count(),
                    'midterm_average' => $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null,
                    'endterm_average' => $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null,

                    'attendance_rate' => $attendanceTotal > 0
                        ? round(($attendancePresentEquivalent / $attendanceTotal) * 100, 1)
                        : null,

                    'punctuality_on_time' => $punctuality->where('status', Punctuality::STATUS_ON_TIME)->count(),
                    'punctuality_late' => $punctuality->where('status', Punctuality::STATUS_LATE)->count(),
                    'punctuality_very_late' => $punctuality->where('status', Punctuality::STATUS_VERY_LATE)->count(),

                    'behaviour_total' => $behaviour->count(),
                    'behaviour_label' => $this->behaviourLabel($behaviour),

                    'performance_label' => $performance['performance_label'] ?? null,
                    'trend' => $performance['trend'] ?? null,
                    'midterm_position' => $performance['midterm_position'] ?? null,
                    'endterm_position' => $performance['endterm_position'] ?? null,
                ];
            })->values();
        }

        $stats = [
            'totalChildren' => $children->count(),
            'accessibleChildren' => $accessibleChildren->count(),
            'blockedChildren' => $blockedChildren->count(),
            'childrenWithMarks' => $marksOverview->where('subjects_count', '>', 0)->count(),
        ];

        return view('parent.dashboard', compact(
            'parent',
            'children',
            'accessibleChildren',
            'blockedChildren',
            'currentAcademicYear',
            'currentTerm',
            'marksOverview',
            'stats'
        ));
    }

    protected function behaviourLabel($behaviourRecords): string
    {
        if ($behaviourRecords->count() === 0) {
            return 'Good';
        }

        $major = $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MAJOR)->count();
        $moderate = $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MODERATE)->count();

        if ($major > 0 || $moderate >= 3) {
            return 'Needs attention';
        }

        return 'Fair';
    }
}
