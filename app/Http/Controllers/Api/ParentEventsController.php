<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParentEventsController extends Controller
{
    public function index(Request $request)
    {
        $upcoming = Event::where('start_datetime', '>=', now())
            ->where(function ($q) {
                $q->where('visibility', 'all')->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime')
            ->get();

        $past = Event::where('start_datetime', '<', now())
            ->where(function ($q) {
                $q->where('visibility', 'all')->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'upcoming' => $upcoming->map(fn ($e) => $this->formatEvent($e)),
            'past'     => $past->map(fn ($e) => $this->formatEvent($e)),
        ]);
    }

    public function calendar(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date',
        ]);

        $events = Event::whereBetween('start_datetime', [$request->start, $request->end])
            ->where(function ($q) {
                $q->where('visibility', 'all')->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(function ($e) {
            $start = $e->start_datetime->copy()->timezone(config('app.timezone'));
            $end = $e->end_datetime?->copy()->timezone(config('app.timezone'));

            return [
                'id'          => $e->id,
                'title'       => $e->title,
                'start'       => $e->is_all_day ? $start->toDateString() : $start->toIso8601String(),
                'end'         => $e->is_all_day ? $end?->toDateString() : $end?->toIso8601String(),
                'start_date'  => $start->toDateString(),
                'end_date'    => $end?->toDateString(),
                'allDay'      => $e->is_all_day,
                'type'        => $e->type,
                'description' => $e->description,
            ];
        }));
    }

    public function show(Event $event)
    {
        if (! in_array($event->visibility, ['all', 'parents'], true)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($this->formatEvent($event));
    }

    public function announcements(Request $request)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $all = Announcement::with(['author', 'targets'])
            ->published()
            ->forParents()
            ->recent(50)
            ->get()
            ->filter(fn ($a) => $a->isRelevantToParent($parent))
            ->values();

        $unreadCount = $all->filter(fn ($a) => ! $a->isReadByParent($parent))->count();

        return response()->json([
            'urgent' => $all
                ->whereIn('type', ['urgent', 'event'])
                ->map(fn ($a) => $this->formatAnnouncement($a, false, $parent))
                ->values(),
            'general' => $all
                ->whereNotIn('type', ['urgent', 'event'])
                ->map(fn ($a) => $this->formatAnnouncement($a, false, $parent))
                ->values(),
            'total' => $unreadCount,
            'all_count' => $all->count(),
            'unread_count' => $unreadCount,
        ]);
    }

    public function showAnnouncement(Request $request, Announcement $announcement)
    {
        $parent = $request->user()->parent;

        if (! $parent || ! $announcement->isRelevantToParent($parent)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $this->markRead($announcement, $parent->id);

        return response()->json([
            'announcement' => $this->formatAnnouncement($announcement->fresh(['author', 'targets']), true, $parent),
            'read' => true,
        ]);
    }

    public function readAnnouncement(Request $request, Announcement $announcement)
    {
        $parent = $request->user()->parent;

        if (! $parent || ! $announcement->isRelevantToParent($parent)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $this->markRead($announcement, $parent->id);

        return response()->json([
            'success' => true,
            'message' => 'Announcement marked as read.',
        ]);
    }

    public function dismissAnnouncement(Request $request, Announcement $announcement)
    {
        return $this->readAnnouncement($request, $announcement);
    }

    public function acknowledgeAnnouncement(Request $request, Announcement $announcement)
    {
        $parent = $request->user()->parent;

        if (! $parent || ! $announcement->isRelevantToParent($parent)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        DB::table('announcement_reads')->updateOrInsert(
            [
                'parent_id' => $parent->id,
                'announcement_id' => $announcement->id,
            ],
            [
                'read_at' => now(),
                'acknowledged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement acknowledged.',
            'acknowledged_at' => now()->toDateTimeString(),
        ]);
    }

    private function markRead(Announcement $announcement, int $parentId): void
    {
        DB::table('announcement_reads')->updateOrInsert(
            [
                'parent_id' => $parentId,
                'announcement_id' => $announcement->id,
            ],
            [
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function formatEvent(Event $e): array
    {
        $start = $e->start_datetime->copy()->timezone(config('app.timezone'));
        $end = $e->end_datetime?->copy()->timezone(config('app.timezone'));

        return [
            'id'             => $e->id,
            'title'          => $e->title,
            'description'    => $e->description,
            'type'           => $e->type,
            'type_label'     => Event::getTypeLabel($e->type),
            'type_color'     => Event::getTypeColor($e->type),
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

    private function formatAnnouncement(Announcement $a, bool $full = false, $parent = null): array
    {
        $readRow = $parent ? DB::table('announcement_reads')
            ->where('parent_id', $parent->id)
            ->where('announcement_id', $a->id)
            ->first() : null;

        return [
            'id' => $a->id,
            'title' => $a->title,
            'message' => $full ? $a->message : Str::limit($a->message, 120),
            'type' => $a->type,
            'type_label' => ucfirst($a->type),
            'type_color' => Announcement::getTypeColor($a->type),
            'type_icon' => Announcement::getTypeIcon($a->type),
            'audience' => $a->audience,
            'requires_acknowledgement' => $a->requiresAcknowledgement(),
            'is_read' => (bool) $readRow,
            'is_acknowledged' => (bool) ($readRow?->acknowledged_at ?? false),
            'read_at' => $readRow?->read_at,
            'acknowledged_at' => $readRow?->acknowledged_at,
            'publish_at' => $a->publish_at?->toDateTimeString(),
            'created_at' => $a->created_at->toDateTimeString(),
            'author' => $a->author->name ?? 'Admin',
        ];
    }
}
