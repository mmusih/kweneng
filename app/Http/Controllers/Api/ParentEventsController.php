<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Event;
use Illuminate\Http\Request;

class ParentEventsController extends Controller
{
    /**
     * GET /api/parent/events
     * All upcoming + recent past events visible to parents.
     */
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
            'upcoming' => $upcoming->map(fn($e) => $this->formatEvent($e)),
            'past'     => $past->map(fn($e) => $this->formatEvent($e)),
        ]);
    }

    /**
     * GET /api/parent/events/calendar?start=&end=
     * FullCalendar-compatible JSON for a date range.
     */
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

        return response()->json($events->map(fn($e) => [
            'id'        => $e->id,
            'title'     => $e->title,
            'start'     => $e->start_datetime->toIso8601String(),
            'end'       => $e->end_datetime?->toIso8601String(),
            'allDay'    => $e->is_all_day,
            'type'      => $e->type,
            'description' => $e->description,
        ]));
    }

    /**
     * GET /api/parent/events/{id}
     * Single event detail.
     */
    public function show(Event $event)
    {
        if (!in_array($event->visibility, ['all', 'parents'])) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($this->formatEvent($event));
    }

    /**
     * GET /api/parent/announcements
     */
    public function announcements(Request $request)
    {
        $parent = $request->user()->parent;

        if (!$parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $all = Announcement::published()
            ->forParents()
            ->recent(30)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent))
            ->values();

        return response()->json([
            'urgent'  => $all->whereIn('type', ['urgent', 'event'])->map(fn($a) => $this->formatAnnouncement($a))->values(),
            'general' => $all->whereNotIn('type', ['urgent', 'event'])->map(fn($a) => $this->formatAnnouncement($a))->values(),
            'total'   => $all->count(),
        ]);
    }

    /**
     * GET /api/parent/announcements/{id}
     */
    public function showAnnouncement(Request $request, Announcement $announcement)
    {
        $parent = $request->user()->parent;

        if (!$parent || !$announcement->isRelevantToParent($parent)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($this->formatAnnouncement($announcement, full: true));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function formatEvent(Event $e): array
    {
        return [
            'id'             => $e->id,
            'title'          => $e->title,
            'description'    => $e->description,
            'type'           => $e->type,
            'type_label'     => \App\Models\Event::getTypeLabel($e->type),
            'type_color'     => \App\Models\Event::getTypeColor($e->type),
            'start_datetime' => $e->start_datetime->toDateTimeString(),
            'end_datetime'   => $e->end_datetime?->toDateTimeString(),
            'is_all_day'     => $e->is_all_day,
            'visibility'     => $e->visibility,
            'days_until'     => max(0, (int) now()->diffInDays($e->start_datetime, false)),
        ];
    }

    private function formatAnnouncement(Announcement $a, bool $full = false): array
    {
        return [
            'id'         => $a->id,
            'title'      => $a->title,
            'message'    => $full ? $a->message : \Illuminate\Support\Str::limit($a->message, 120),
            'type'       => $a->type,
            'type_label' => ucfirst($a->type),
            'type_color' => Announcement::getTypeColor($a->type),
            'type_icon'  => Announcement::getTypeIcon($a->type),
            'audience'   => $a->audience,
            'publish_at' => $a->publish_at?->toDateTimeString(),
            'created_at' => $a->created_at->toDateTimeString(),
            'author'     => $a->author->name ?? 'Admin',
        ];
    }
}
