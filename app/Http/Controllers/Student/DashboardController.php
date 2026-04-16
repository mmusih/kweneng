<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\LibraryBorrowing;
use App\Models\Mark;
use App\Models\Punctuality;
use App\Models\StudentSubject;
use App\Models\Term;
use App\Services\StudentPerformanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected StudentPerformanceService $studentPerformanceService
    ) {}

    public function index()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'student') {
            return redirect()->route('login')->withErrors([
                'error' => 'Unauthorized access',
            ]);
        }

        $cacheKey = 'student_dashboard_v3_' . $user->id . '_' . now()->format('Y-m-d-H');

        $dashboardData = Cache::remember($cacheKey, 300, function () use ($user) {
            $data = [
                'student' => null,
                'currentAcademicYear' => null,
                'currentTerm' => null,
                'stats' => [
                    'subjectsAssigned' => 0,
                    'subjectsWithMarks' => 0,
                    'midtermAverage' => null,
                    'endtermAverage' => null,
                    'feesBlocked' => false,
                ],
                'latestMarks' => collect(),
                'attendanceSummary' => null,
                'punctualitySummary' => null,
                'behaviourSummary' => null,
                'performance' => null,
                'borrowings' => collect(),
                'borrowingsCount' => 0,
                'overdueBorrowingsCount' => 0,
            ];

            if (!$user->student) {
                return $data;
            }

            $student = $user->student;
            $student->load(['user', 'currentClass.academicYear']);

            $currentAcademicYear = AcademicYear::where(function ($query) {
                $query->where('status', 'open')
                    ->orWhere('status', 'active');
            })
                ->orderByDesc('created_at')
                ->first();

            $currentTerm = null;

            if ($currentAcademicYear) {
                $currentTerm = Term::where('academic_year_id', $currentAcademicYear->id)
                    ->where('status', 'active')
                    ->orderBy('start_date')
                    ->first();
            }

            $data['student'] = $student;
            $data['currentAcademicYear'] = $currentAcademicYear;
            $data['currentTerm'] = $currentTerm;
            $data['stats']['feesBlocked'] = (bool) $student->fees_blocked;

            if ($currentAcademicYear) {
                $data['stats']['subjectsAssigned'] = StudentSubject::where('student_id', $student->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->count();
            }

            if ($currentAcademicYear && $currentTerm) {
                $marks = Mark::where('student_id', $student->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->with(['subject', 'teacher.user'])
                    ->get();

                $midtermScores = $marks->pluck('midterm_score')->filter(fn($score) => $score !== null);
                $endtermScores = $marks->pluck('endterm_score')->filter(fn($score) => $score !== null);

                $data['stats']['subjectsWithMarks'] = $marks->count();
                $data['stats']['midtermAverage'] = $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null;
                $data['stats']['endtermAverage'] = $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null;

                $data['latestMarks'] = $marks
                    ->sortBy(fn($mark) => $mark->subject->name ?? '')
                    ->take(6)
                    ->values();

                $attendanceRecords = Attendance::where('student_id', $student->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                $attendanceTotal = $attendanceRecords->count();
                $attendancePresentEquivalent = $attendanceRecords->whereIn('status', [
                    Attendance::STATUS_PRESENT,
                    Attendance::STATUS_LATE,
                    Attendance::STATUS_EXCUSED,
                ])->count();

                $data['attendanceSummary'] = [
                    'present' => $attendanceRecords->where('status', Attendance::STATUS_PRESENT)->count(),
                    'absent' => $attendanceRecords->where('status', Attendance::STATUS_ABSENT)->count(),
                    'late' => $attendanceRecords->where('status', Attendance::STATUS_LATE)->count(),
                    'excused' => $attendanceRecords->where('status', Attendance::STATUS_EXCUSED)->count(),
                    'rate' => $attendanceTotal > 0 ? round(($attendancePresentEquivalent / $attendanceTotal) * 100, 1) : null,
                ];

                $punctualityRecords = Punctuality::where('student_id', $student->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                $data['punctualitySummary'] = [
                    'on_time' => $punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count(),
                    'late' => $punctualityRecords->where('status', Punctuality::STATUS_LATE)->count(),
                    'very_late' => $punctualityRecords->where('status', Punctuality::STATUS_VERY_LATE)->count(),
                    'absent' => $punctualityRecords->where('status', Punctuality::STATUS_ABSENT)->count(),
                ];

                $behaviourRecords = BehaviourRecord::where('student_id', $student->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->latest('record_date')
                    ->latest('id')
                    ->get();

                $data['behaviourSummary'] = [
                    'total' => $behaviourRecords->count(),
                    'minor' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MINOR)->count(),
                    'moderate' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MODERATE)->count(),
                    'major' => $behaviourRecords->where('severity', BehaviourRecord::SEVERITY_MAJOR)->count(),
                    'latest' => $behaviourRecords->first(),
                    'label' => $this->behaviourLabel($behaviourRecords),
                ];

                $data['performance'] = $this->studentPerformanceService
                    ->getStudentTermPerformance($student, $currentAcademicYear->id, $currentTerm->id);
            }

            $activeBorrowingsQuery = LibraryBorrowing::with(['bookCopy.book'])
                ->where('student_id', $student->id)
                ->whereNull('returned_at');

            $data['borrowings'] = (clone $activeBorrowingsQuery)
                ->latest('issued_at')
                ->take(5)
                ->get();

            $data['borrowingsCount'] = (clone $activeBorrowingsQuery)->count();

            $data['overdueBorrowingsCount'] = (clone $activeBorrowingsQuery)
                ->whereDate('due_at', '<', now()->toDateString())
                ->count();

            return $data;
        });

        return view('student.dashboard', [
            'student' => $dashboardData['student'] ?? null,
            'currentAcademicYear' => $dashboardData['currentAcademicYear'] ?? null,
            'currentTerm' => $dashboardData['currentTerm'] ?? null,
            'stats' => $dashboardData['stats'] ?? [
                'subjectsAssigned' => 0,
                'subjectsWithMarks' => 0,
                'midtermAverage' => null,
                'endtermAverage' => null,
                'feesBlocked' => false,
            ],
            'latestMarks' => $dashboardData['latestMarks'] ?? collect(),
            'attendanceSummary' => $dashboardData['attendanceSummary'] ?? null,
            'punctualitySummary' => $dashboardData['punctualitySummary'] ?? null,
            'behaviourSummary' => $dashboardData['behaviourSummary'] ?? null,
            'performance' => $dashboardData['performance'] ?? null,
            'borrowings' => $dashboardData['borrowings'] ?? collect(),
            'borrowingsCount' => $dashboardData['borrowingsCount'] ?? 0,
            'overdueBorrowingsCount' => $dashboardData['overdueBorrowingsCount'] ?? 0,
        ]);
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
