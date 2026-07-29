<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\BehaviourRecord;
use App\Models\Event;
use App\Models\Homework;
use App\Models\HomeworkMark;
use App\Models\LibraryBorrowing;
use App\Models\Mark;
use App\Models\Term;
use App\Services\StudentPerformanceService;
use Illuminate\Http\Request;

class ParentDashboardController extends Controller
{
    public function __construct(
        protected StudentPerformanceService $studentPerformanceService
    ) {}

    /**
     * GET /api/parent/dashboard
     * Full dashboard summary.
     */
    public function index(Request $request)
    {
        $user   = $request->user();
        $parent = $user->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $children = $parent->students()->with([
            'user',
            'currentClass',
            'studentSubjects',
            'latestFeeBalance.academicYear',
            'latestFeeBalance.term',
        ])->get();

        $currentAcademicYear = AcademicYear::where(function ($q) {
            $q->where('status', 'open')->orWhere('status', 'active');
        })->orderByDesc('created_at')->first();

        $currentTerm = $currentAcademicYear
            ? Term::where('academic_year_id', $currentAcademicYear->id)
            ->where('status', 'active')
            ->orderBy('start_date')
            ->first()
            : null;

        $blockedChildren    = $children->filter(fn($c) => (bool) $c->fees_blocked)->values();
        $accessibleChildren = $children->filter(fn($c) => !(bool) $c->fees_blocked)->values();
        $studentIds = $children->pluck('id')->map(fn ($id) => (int) $id)->values();
        $unreadHomeworkTotal = $this->unreadHomeworkCount($parent->id, $studentIds);

        // Announcements shown on the dashboard must be relevant to this parent and unread.
        // Full history remains available on the Announcements screen.
        $announcements = Announcement::with(['author', 'targets'])
            ->published()
            ->forParents()
            ->unreadByParent($parent->id)
            ->recent(5)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent))
            ->values();

        $importantAnnouncements = Announcement::with(['author', 'targets'])
            ->published()
            ->forParents()
            ->unreadByParent($parent->id)
            ->whereIn('type', ['urgent', 'event'])
            ->recent(3)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent))
            ->values();

        // Upcoming events
        $upcomingEvents = Event::where('start_datetime', '>=', now())
            ->where(function ($q) {
                $q->where('visibility', 'all')->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime')
            ->limit(5)
            ->get();

        // Children overviews
        $childrenData = $children->map(function ($child) use (
            $currentAcademicYear,
            $currentTerm,
            $parent
        ) {
            $isBlocked = (bool) $child->fees_blocked;

            $activeBorrowings = LibraryBorrowing::with(['bookCopy.book'])
                ->where('student_id', $child->id)
                ->whereNull('returned_at')
                ->latest('issued_at')
                ->get();

            $overdueCount = $activeBorrowings
                ->filter(fn($b) => $b->due_at && $b->due_at->isPast())
                ->count();

            $marks = ($currentAcademicYear && $currentTerm)
                ? Mark::where('student_id', $child->id)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('term_id', $currentTerm->id)
                ->get()
                : collect();

            $midtermAvg = $marks->pluck('midterm_score')->filter(fn($s) => $s !== null)->avg();
            $endtermAvg = $marks->pluck('endterm_score')->filter(fn($s) => $s !== null)->avg();

            $performance = ($currentAcademicYear && $currentTerm)
                ? $this->studentPerformanceService->getStudentTermPerformance(
                    $child,
                    $currentAcademicYear->id,
                    $currentTerm->id
                )
                : [];

            $attendanceRate = null;
            if ($currentAcademicYear && $currentTerm) {
                $attendanceRecords = Attendance::where('student_id', $child->id)
                    ->where('academic_year_id', $currentAcademicYear->id)
                    ->where('term_id', $currentTerm->id)
                    ->get();

                if ($attendanceRecords->count() > 0) {
                    $present = $attendanceRecords->whereIn('status', [
                        Attendance::STATUS_PRESENT,
                        Attendance::STATUS_LATE,
                        Attendance::STATUS_EXCUSED,
                    ])->count();
                    $attendanceRate = round(($present / $attendanceRecords->count()) * 100, 1);
                }
            }

            $behaviourRecords = ($currentAcademicYear && $currentTerm)
                ? BehaviourRecord::where('student_id', $child->id)
                ->where('academic_year_id', $currentAcademicYear->id)
                ->where('term_id', $currentTerm->id)
                ->get()
                : collect();

            $homeworkCount = HomeworkMark::query()
                ->where('student_id', $child->id)
                ->whereHas('homework')
                ->count();

            $unreadHomeworkCount = $this->unreadHomeworkCount($parent->id, collect([$child->id]));

            return [
                'id'           => $child->id,
                'name'         => $child->user->name ?? 'Unknown',
                'admission_no' => $child->admission_no,
                'class'        => $child->currentClass->name ?? null,
                'photo'        => $child->photo ? asset('storage/' . $child->photo) : null,
                'is_blocked'   => $isBlocked,
                'identity' => [
                    'nationality' => $child->nationality,
                    'document_type' => $child->identity_document_type,
                    'document_label' => $child->identityDocumentLabel(),
                    'document_number' => $child->identity_document_number,
                    'display' => $child->identityDisplay(),
                ],
                'profile' => [
                    'complete' => $child->isProfileComplete(),
                    'missing_fields' => $child->profileCompletionIssues(),
                    'profile_updated_by_parent_at' => $child->profile_updated_by_parent_at?->toDateTimeString(),
                ],
                'emergency_contact' => [
                    'name' => $child->emergency_contact_name,
                    'relationship' => $child->emergency_contact_relationship,
                    'phone' => $child->emergency_contact_phone,
                    'alt_phone' => $child->emergency_contact_alt_phone,
                    'address' => $child->emergency_contact_address,
                    'medical_notes' => $child->medical_notes,
                ],
                'fees'        => [
                    'closing_balance' => $child->latestFeeBalance ? (float) $child->latestFeeBalance->closing_balance : null,
                    'status'          => $child->latestFeeBalance ? $child->latestFeeBalance->status : 'Not Available',
                    'last_updated'    => $child->latestFeeBalance?->updated_at?->toDateTimeString(),
                    'academic_year'   => $child->latestFeeBalance?->academicYear?->year_name,
                    'term'            => $child->latestFeeBalance?->term?->name,
                ],
                'marks'        => $isBlocked ? null : [
                    'midterm_average'  => $midtermAvg !== null ? round($midtermAvg, 1) : null,
                    'endterm_average'  => $endtermAvg !== null ? round($endtermAvg, 1) : null,
                    'midterm_position' => $performance['midterm_position'] ?? null,
                    'endterm_position' => $performance['endterm_position'] ?? null,
                    'trend'            => ($performance['trend'] ?? 'N/A') !== 'N/A' ? $performance['trend'] : null,
                    'performance_label' => $performance['performance_label'] ?? null,
                ],
                'attendance_rate' => $attendanceRate,
                'behaviour'       => [
                    'label' => $this->behaviourLabel($behaviourRecords),
                    'total' => $behaviourRecords->count(),
                ],
                'homework' => [
                    'current_term_count' => $homeworkCount,
                    'unread_count' => $unreadHomeworkCount,
                ],
                'library' => [
                    'borrowed' => $activeBorrowings->count(),
                    'overdue'  => $overdueCount,
                    'books'    => $activeBorrowings->take(5)->map(fn($b) => [
                        'title'    => $b->bookCopy->book->title ?? 'Unknown',
                        'due_at'   => $b->due_at?->toDateString(),
                        'overdue'  => $b->due_at && $b->due_at->isPast(),
                    ])->values(),
                ],
            ];
        });

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'academic_year' => $currentAcademicYear ? [
                'id'        => $currentAcademicYear->id,
                'year_name' => $currentAcademicYear->year_name,
            ] : null,
            'current_term' => $currentTerm ? [
                'id'         => $currentTerm->id,
                'name'       => $currentTerm->name,
                'start_date' => $currentTerm->start_date->toDateString(),
                'end_date'   => $currentTerm->end_date->toDateString(),
                'days_left'  => max(0, (int) now()->copy()->startOfDay()->diffInDays($currentTerm->end_date->copy()->startOfDay(), false)),
            ] : null,
            'stats' => [
                'total_children'      => $children->count(),
                'blocked_children'    => $blockedChildren->count(),
                'accessible_children' => $accessibleChildren->count(),
                'unread_homework' => $unreadHomeworkTotal,
                'incomplete_profiles' => $children->filter(fn ($child) => ! $child->isProfileComplete())->count(),
            ],
            'important_announcements' => $importantAnnouncements->map(fn($a) => $this->formatAnnouncement($a)),
            'announcements'           => $announcements->map(fn($a) => $this->formatAnnouncement($a)),
            'upcoming_events'         => $upcomingEvents->map(fn($e) => $this->formatEvent($e)),
            'children'                => $childrenData,
        ]);
    }


    private function unreadHomeworkCount(int $parentId, $studentIds): int
    {
        $studentIds = collect($studentIds)->filter()->values();

        if ($studentIds->isEmpty()) {
            return 0;
        }

        return HomeworkMark::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('homework')
            ->whereNotExists(function ($query) use ($parentId) {
                $query->selectRaw('1')
                    ->from('homework_parent_reads')
                    ->where('homework_parent_reads.parent_id', $parentId)
                    ->whereColumn('homework_parent_reads.student_id', 'homework_marks.student_id')
                    ->whereColumn('homework_parent_reads.homework_id', 'homework_marks.homework_id');
            })
            ->count();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function formatAnnouncement($a): array
    {
        return [
            'id'         => $a->id,
            'title'      => $a->title,
            'message'    => $a->message,
            'type'       => $a->type,
            'audience'   => $a->audience,
            'publish_at' => $a->publish_at?->toDateTimeString(),
            'created_at' => $a->created_at->toDateTimeString(),
            'author'     => $a->author->name ?? 'Admin',
            'requires_acknowledgement' => method_exists($a, 'requiresAcknowledgement') ? $a->requiresAcknowledgement() : false,
        ];
    }

    private function formatEvent($e): array
    {
        $start = $e->start_datetime->copy()->timezone(config('app.timezone'));
        $end = $e->end_datetime?->copy()->timezone(config('app.timezone'));

        return [
            'id'             => $e->id,
            'title'          => $e->title,
            'description'    => $e->description,
            'type'           => $e->type,
            'start_date'     => $start->toDateString(),
            'end_date'       => $end?->toDateString(),
            'start_datetime' => $e->is_all_day ? $start->toDateString() : $start->toDateTimeString(),
            'end_datetime'   => $e->is_all_day ? $end?->toDateString() : $end?->toDateTimeString(),
            'is_all_day'     => $e->is_all_day,
            'visibility'     => $e->visibility,
            'days_until'     => $this->calendarDaysUntil($start),
        ];
    }

    private function calendarDaysUntil($date): int
    {
        return max(
            0,
            (int) now()
                ->copy()
                ->startOfDay()
                ->diffInDays($date->copy()->startOfDay(), false)
        );
    }

    private function behaviourLabel($records): string
    {
        if ($records->count() === 0) return 'Good';
        $major    = $records->where('severity', BehaviourRecord::SEVERITY_MAJOR)->count();
        $moderate = $records->where('severity', BehaviourRecord::SEVERITY_MODERATE)->count();
        if ($major > 0 || $moderate >= 3) return 'Needs attention';
        return 'Fair';
    }
}
