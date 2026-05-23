<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventsController extends Controller
{
    /**
     * Show upcoming events list + calendar view.
     */
    public function index()
    {
        $upcomingEvents = Event::where('start_datetime', '>=', now())
            ->where(function ($q) {
                $q->where('visibility', 'all')
                    ->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime')
            ->get();

        $pastEvents = Event::where('start_datetime', '<', now())
            ->where(function ($q) {
                $q->where('visibility', 'all')
                    ->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime', 'desc')
            ->limit(10)
            ->get();

        return view('parent.events.index', compact('upcomingEvents', 'pastEvents'));
    }

    /**
     * Return events as JSON for FullCalendar AJAX.
     */
    public function getEvents(Request $request)
    {
        $start = $request->get('start');
        $end   = $request->get('end');

        $events = Event::whereBetween('start_datetime', [$start, $end])
            ->where(function ($q) {
                $q->where('visibility', 'all')
                    ->orWhere('visibility', 'parents');
            })
            ->orderBy('start_datetime')
            ->get();

        return response()->json($events->map(fn($event) => [
            'id'        => $event->id,
            'title'     => $event->title,
            'start'     => $event->start_datetime->toIso8601String(),
            'end'       => $event->end_datetime?->toIso8601String(),
            'allDay'    => $event->is_all_day,
            'className' => 'event-' . $event->type,
            'extendedProps' => [
                'type'        => $event->type,
                'description' => $event->description,
            ],
        ]));
    }

    /**
     * Show announcements list.
     */
    public function announcements()
    {
        $parent = Auth::user()->parent;

        $announcements = Announcement::published()
            ->forParents()
            ->recent(20)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent))
            ->values();

        $urgentAnnouncements = $announcements->whereIn('type', ['urgent', 'event']);
        $generalAnnouncements = $announcements->whereNotIn('type', ['urgent', 'event']);

        return view('parent.events.announcements', compact('announcements', 'urgentAnnouncements', 'generalAnnouncements'));
    }
}
