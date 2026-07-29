<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Scheme;
use App\Models\SchemeItem;
use App\Models\SchemeItemSubtopic;
use App\Models\SchemeProgressLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherSchemeController extends Controller
{
    public function index()
    {
        $teacher = auth()->user()?->teacher;
        abort_unless($teacher, 403, 'Teacher profile not found.');

        $academicYear = AcademicYear::current();

        $schemes = Scheme::with(['teacherSubject.class', 'teacherSubject.subject', 'items.subtopics', 'logs'])
            ->whereHas('teacherSubject', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->when($academicYear, fn ($query) => $query->where('academic_year_id', $academicYear->id))
            ->whereIn('status', [Scheme::STATUS_APPROVED, Scheme::STATUS_ACTIVE, Scheme::STATUS_SUBMITTED])
            ->orderBy('title')
            ->get();

        return response()->json($schemes->map(fn (Scheme $scheme) => $this->summary($scheme))->values());
    }

    public function show(Scheme $scheme)
    {
        $this->authoriseOwnScheme($scheme);

        $scheme->load(['teacherSubject.class', 'teacherSubject.subject', 'items.term', 'items.subtopics']);

        return response()->json([
            ...$this->summary($scheme),
            'expected_pct' => $scheme->expectedPct(),
            'pacing_status' => $scheme->pacingStatus(),
            'terms' => $scheme->items
                ->whereNotNull('term_id')
                ->groupBy('term_id')
                ->map(function ($items, $termId) {
                    $term = $items->first()->term;

                    return [
                        'term_id' => (int) $termId,
                        'term_name' => $term?->name,
                        'weeks' => $items->groupBy('week_number')->map(function ($weekItems, $week) {
                            return [
                                'week' => (int) $week,
                                'topics' => $weekItems->sortBy('planned_order')->map(fn (SchemeItem $item) => $this->itemPayload($item))->values(),
                            ];
                        })->values(),
                    ];
                })->values(),
        ]);
    }

    public function updateItem(Request $request, SchemeItem $item)
    {
        $scheme = $item->scheme;
        $this->authoriseOwnScheme($scheme);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(SchemeItem::statuses()))],
            'teacher_comment' => ['nullable', 'string'],
        ]);

        $oldStatus = $item->status;
        $completedAt = $validated['status'] === SchemeItem::STATUS_COMPLETED ? now() : null;
        $completedBy = $validated['status'] === SchemeItem::STATUS_COMPLETED ? auth()->id() : null;

        $item->update([
            'status' => $validated['status'],
            'completed_at' => $completedAt,
            'completed_by' => $completedBy,
            'teacher_comment' => $validated['teacher_comment'] ?? $item->teacher_comment,
        ]);

        if ($validated['status'] === SchemeItem::STATUS_COMPLETED) {
            $item->subtopics()->where('status', '!=', SchemeItemSubtopic::STATUS_COMPLETED)->update([
                'status' => SchemeItemSubtopic::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        }

        SchemeProgressLog::create([
            'scheme_id' => $scheme->id,
            'scheme_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'mobile_topic_status_updated',
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'comment' => $validated['teacher_comment'] ?? null,
        ]);

        $scheme->refresh()->load('items.subtopics');

        return response()->json([
            'item' => $this->itemPayload($item->fresh('subtopics')),
            'overall_pct' => $scheme->completionPct(),
            'expected_pct' => $scheme->expectedPct(),
            'pacing_status' => $scheme->pacingStatus(),
        ]);
    }

    public function toggleSubtopic(SchemeItemSubtopic $subtopic)
    {
        $item = $subtopic->item;
        $scheme = $item->scheme;
        $this->authoriseOwnScheme($scheme);

        $oldStatus = $subtopic->status;
        $newStatus = $subtopic->status === SchemeItemSubtopic::STATUS_COMPLETED
            ? SchemeItemSubtopic::STATUS_NOT_STARTED
            : SchemeItemSubtopic::STATUS_COMPLETED;

        $subtopic->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === SchemeItemSubtopic::STATUS_COMPLETED ? now() : null,
            'completed_by' => $newStatus === SchemeItemSubtopic::STATUS_COMPLETED ? auth()->id() : null,
        ]);

        $remaining = $item->subtopics()->where('status', '!=', SchemeItemSubtopic::STATUS_COMPLETED)->count();
        $item->update([
            'status' => $remaining === 0 ? SchemeItem::STATUS_COMPLETED : SchemeItem::STATUS_IN_PROGRESS,
            'completed_at' => $remaining === 0 ? now() : null,
            'completed_by' => $remaining === 0 ? auth()->id() : null,
        ]);

        SchemeProgressLog::create([
            'scheme_id' => $scheme->id,
            'scheme_item_id' => $item->id,
            'scheme_item_subtopic_id' => $subtopic->id,
            'user_id' => auth()->id(),
            'action' => 'mobile_subtopic_toggled',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        $scheme->refresh()->load('items.subtopics');

        return response()->json([
            'subtopic' => [
                'id' => $subtopic->id,
                'status' => $newStatus,
                'completed_at' => $subtopic->fresh()->completed_at?->toIso8601String(),
            ],
            'item' => $this->itemPayload($item->fresh('subtopics')),
            'overall_pct' => $scheme->completionPct(),
        ]);
    }

    private function authoriseOwnScheme(Scheme $scheme): void
    {
        $teacher = auth()->user()?->teacher;
        abort_unless($teacher, 403, 'Teacher profile not found.');
        $scheme->loadMissing('teacherSubject');
        abort_unless($scheme->teacherSubject?->teacher_id === $teacher->id, 403);
    }

    private function summary(Scheme $scheme): array
    {
        return [
            'id' => $scheme->id,
            'title' => $scheme->title,
            'class_name' => $scheme->teacherSubject?->class?->name,
            'subject_name' => $scheme->teacherSubject?->subject?->name,
            'academic_year' => $scheme->academicYear?->year_name,
            'status' => $scheme->status,
            'overall_pct' => $scheme->completionPct(),
            'expected_pct' => $scheme->expectedPct(),
            'pacing_status' => $scheme->pacingStatus(),
            'last_progress_at' => $scheme->lastProgressAt()?->toIso8601String(),
        ];
    }

    private function itemPayload(SchemeItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'term_id' => $item->term_id,
            'term_name' => $item->term?->name,
            'week_number' => $item->week_number,
            'planned_order' => $item->planned_order,
            'status' => $item->status,
            'completed_at' => $item->completed_at?->toIso8601String(),
            'is_behind' => $item->isBehindSchedule(),
            'teacher_comment' => $item->teacher_comment,
            'subtopics' => $item->subtopics->map(fn (SchemeItemSubtopic $subtopic) => [
                'id' => $subtopic->id,
                'title' => $subtopic->title,
                'sort_order' => $subtopic->sort_order,
                'status' => $subtopic->status,
                'completed_at' => $subtopic->completed_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
