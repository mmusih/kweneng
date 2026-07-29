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

        return response()->json($events->map(function ($event) {
            $start = $event->start_datetime->copy()->timezone(config('app.timezone'));
            $end = $event->end_datetime?->copy()->timezone(config('app.timezone'));

            return [
                'id'        => $event->id,
                'title'     => $event->title,
                'start'     => $event->is_all_day ? $start->toDateString() : $start->toIso8601String(),
                'end'       => $event->is_all_day ? $end?->toDateString() : $end?->toIso8601String(),
                'allDay'    => $event->is_all_day,
                'className' => 'event-' . $event->type,
                'extendedProps' => [
                    'type'        => $event->type,
                    'description' => $event->description,
                    'start_date'  => $start->toDateString(),
                    'end_date'    => $end?->toDateString(),
                ],
            ];
        }));
    }

    /**
     * Show announcements list and mark all as read for this parent.
     */
    public function announcements()
    {
        $parent = Auth::user()->parent;

        // Mark all unread announcements as read now that the parent is viewing this page
        $unread = Announcement::published()
            ->forParents()
            ->unreadByParent($parent->id)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent));

        foreach ($unread as $announcement) {
            $announcement->markReadByParent($parent);
        }

        // Fetch all relevant announcements to display (no unread filter here — show full history)
        $announcements = Announcement::published()
            ->forParents()
            ->recent(20)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent))
            ->values();

        $urgentAnnouncements  = $announcements->whereIn('type', ['urgent', 'event']);
        $generalAnnouncements = $announcements->whereNotIn('type', ['urgent', 'event']);

        return view('parent.events.announcements', compact('announcements', 'urgentAnnouncements', 'generalAnnouncements'));
    }
}
