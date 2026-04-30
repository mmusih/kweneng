<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\LibraryBorrowing;
use App\Models\Mark;
use App\Models\Punctuality;
use App\Models\Term;
use App\Services\StudentPerformanceService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected StudentPerformanceService $studentPerformanceService
    ) {}

    public function index()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'parent') {
            return redirect()->route('login')->withErrors([
                'error' => 'Unauthorized access',
            ]);
        }

        $dashboardData = (function () use ($user) {
            $data = [
                'children' => collect(),
                'blockedChildren' => collect(),
                'accessibleChildren' => collect(),
                'currentAcademicYear' => null,
                'currentTerm' => null,
                'stats' => [
                    'totalChildren' => 0,
                    'accessibleChildren' => 0,
                    'blockedChildren' => 0,
                    'childrenWithMarks' => 0,
                    'borrowedBooks' => 0,
                    'overdueBooks' => 0,
                ],
                'marksOverview' => [],
                'childrenLibrarySummary' => [],
            ];

            $parent = $user->parent;

            if (!$parent) {
                return $data;
            }

            $children = $parent->students()->with([
                'user',
                'currentClass',
            ])->get();

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

            $blockedChildren = $children->filter(fn ($child) => (bool) $child->fees_blocked)->values();
            $accessibleChildren = $children->filter(fn ($child) => !(bool) $child->fees_blocked)->values();

            $data['children'] = $children;
            $data['blockedChildren'] = $blockedChildren;
            $data['accessibleChildren'] = $accessibleChildren;
            $data['currentAcademicYear'] = $currentAcademicYear;
            $data['currentTerm'] = $currentTerm;

            $data['stats']['totalChildren'] = $children->count();
            $data['stats']['accessibleChildren'] = $accessibleChildren->count();
            $data['stats']['blockedChildren'] = $blockedChildren->count();

            $marksOverview = [];
            $childrenLibrarySummary = [];
            $childrenWithMarks = 0;
            $borrowedBooks = 0;
            $overdueBooks = 0;

            foreach ($children as $child) {
                $activeBorrowings = LibraryBorrowing::with(['bookCopy.book'])
                    ->where('student_id', $child->id)
                    ->whereNull('returned_at')
                    ->latest('issued_at')
                    ->get();

                $childOverdueBooks = $activeBorrowings
                    ->filter(fn ($borrowing) => $borrowing->due_at && $borrowing->due_at->isPast())
                    ->count();

                $borrowedBooks += $activeBorrowings->count();
                $overdueBooks += $childOverdueBooks;

                $childrenLibrarySummary[] = [
                    'student_id' => $child->id,
                    'student_name' => $child->user->name ?? 'Unknown Student',
                    'class_name' => $child->currentClass->name ?? 'N/A',
                    'borrowed_books' => $activeBorrowings->count(),
                    'overdue_books' => $childOverdueBooks,
                    'borrowings' => $activeBorrowings->take(5),
                ];

                if (!$currentAcademicYear || !$currentTerm) {
                    $marksOverview[] = [
                        'student_name' => $child->user->name ?? 'Unknown Student',
                        'admission_no' => $child->admission_no ?? 'N/A',
                        'class_name' => $child->currentClass->name ?? 'N/A',
                        'subjects_count' => 0,
                        'midterm_average' => null,
                        'endterm_average' => null,
                        'performance_label' => 'N/A',
                        'trend' => 'N/A',
                        'midterm_position' => null,
                        'endterm_position' => null,
                        'attendance_rate' => null,
                        'punctuality_on_time' => 0,
                        'punctuality_late' => 0,
                        'punctuality_very_late' => 0,
                        'behaviour_total' => 0,
                        'behaviour_label' => 'Good',
                    ];
                    continue;
                }

                $marks = Mark::where('student_id', $child->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                if ($marks->count() > 0) {
                    $childrenWithMarks++;
                }

                $midtermScores = $marks->pluck('midterm_score')->filter(fn ($score) => $score !== null);
                $endtermScores = $marks->pluck('endterm_score')->filter(fn ($score) => $score !== null);

                $performance = $this->studentPerformanceService
                    ->getStudentTermPerformance($child, $currentAcademicYear->id, $currentTerm->id);

                $attendanceRecords = Attendance::where('student_id', $child->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                $attendanceRate = null;

                if ($attendanceRecords->count() > 0) {
                    $presentEquivalent = $attendanceRecords->whereIn('status', [
                        Attendance::STATUS_PRESENT,
                        Attendance::STATUS_LATE,
                        Attendance::STATUS_EXCUSED,
                    ])->count();

                    $attendanceRate = round(($presentEquivalent / $attendanceRecords->count()) * 100, 1);
                }

                $punctualityRecords = Punctuality::where('student_id', $child->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                $behaviourRecords = BehaviourRecord::where('student_id', $child->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                $marksOverview[] = [
                    'student_name' => $child->user->name ?? 'Unknown Student',
                    'admission_no' => $child->admission_no ?? 'N/A',
                    'class_name' => $child->currentClass->name ?? 'N/A',
                    'subjects_count' => $marks->count(),
                    'midterm_average' => $midtermScores->isNotEmpty() ? round($midtermScores->avg(), 2) : null,
                    'endterm_average' => $endtermScores->isNotEmpty() ? round($endtermScores->avg(), 2) : null,
                    'performance_label' => $performance['performance_label'] ?? 'N/A',
                    'trend' => $performance['trend'] ?? 'N/A',
                    'midterm_position' => $performance['midterm_position'] ?? null,
                    'endterm_position' => $performance['endterm_position'] ?? null,
                    'attendance_rate' => $attendanceRate,
                    'punctuality_on_time' => $punctualityRecords->where('status', Punctuality::STATUS_ON_TIME)->count(),
                    'punctuality_late' => $punctualityRecords->where('status', Punctuality::STATUS_LATE)->count(),
                    'punctuality_very_late' => $punctualityRecords->where('status', Punctuality::STATUS_VERY_LATE)->count(),
                    'behaviour_total' => $behaviourRecords->count(),
                    'behaviour_label' => $this->behaviourLabel($behaviourRecords),
                ];
            }

            $data['stats']['childrenWithMarks'] = $childrenWithMarks;
            $data['stats']['borrowedBooks'] = $borrowedBooks;
            $data['stats']['overdueBooks'] = $overdueBooks;
            $data['marksOverview'] = $marksOverview;
            $data['childrenLibrarySummary'] = $childrenLibrarySummary;

            return $data;
        })();

        return view('parent.dashboard', [
            'children' => $dashboardData['children'] ?? collect(),
            'blockedChildren' => $dashboardData['blockedChildren'] ?? collect(),
            'accessibleChildren' => $dashboardData['accessibleChildren'] ?? collect(),
            'currentAcademicYear' => $dashboardData['currentAcademicYear'] ?? null,
            'currentTerm' => $dashboardData['currentTerm'] ?? null,
            'stats' => $dashboardData['stats'] ?? [
                'totalChildren' => 0,
                'accessibleChildren' => 0,
                'blockedChildren' => 0,
                'childrenWithMarks' => 0,
                'borrowedBooks' => 0,
                'overdueBooks' => 0,
            ],
            'marksOverview' => $dashboardData['marksOverview'] ?? [],
            'childrenLibrarySummary' => $dashboardData['childrenLibrarySummary'] ?? [],
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