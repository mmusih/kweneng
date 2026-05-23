<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventComment;
use App\Models\ClassModel;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index()
    {
        $events = Event::with(['classModel', 'academicYear', 'creator', 'comments'])
            ->orderBy('start_datetime', 'desc')
            ->paginate(20);

        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        $classes = ClassModel::all();
        $academicYears = AcademicYear::where('status', '!=', 'closed')
            ->orderBy('year_name', 'desc')
            ->get();

        return view('admin.events.create', compact('classes', 'academicYears'));
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'start_datetime'     => 'required|date',
            'end_datetime'       => 'nullable|date|after_or_equal:start_datetime',
            'type'               => 'required|in:holiday,exam,meeting,activity,ceremony,other',
            'visibility'         => 'required|in:all,parents,teachers,students,specific_class',
            'class_id'           => 'nullable|exists:classes,id',
            'academic_year_id'   => 'nullable|exists:academic_years,id',
            'is_all_day'         => 'nullable|boolean',
            'is_recurring'       => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|max:50',
        ]);

        // Checkboxes are absent from the request when unchecked — force a proper boolean
        $validated['is_all_day']   = $request->boolean('is_all_day');
        $validated['is_recurring'] = $request->boolean('is_recurring');

        $validated['created_by']      = Auth::id();
        $validated['created_by_role'] = Auth::user()->role;

        // Handle visibility logic
        if ($validated['visibility'] === 'specific_class' && empty($validated['class_id'])) {
            return back()
                ->withErrors(['class_id' => 'Class is required when visibility is set to specific class.'])
                ->withInput();
        }

        Event::create($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        $event->load(['classModel', 'academicYear', 'creator', 'comments.user']);
        return view('admin.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        $classes = ClassModel::all();
        $academicYears = AcademicYear::where('status', '!=', 'closed')
            ->orderBy('year_name', 'desc')
            ->get();

        return view('admin.events.edit', compact('event', 'classes', 'academicYears'));
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'start_datetime'     => 'required|date',
            'end_datetime'       => 'nullable|date|after_or_equal:start_datetime',
            'type'               => 'required|in:holiday,exam,meeting,activity,ceremony,other',
            'visibility'         => 'required|in:all,parents,teachers,students,specific_class',
            'class_id'           => 'nullable|exists:classes,id',
            'academic_year_id'   => 'nullable|exists:academic_years,id',
            'is_all_day'         => 'nullable|boolean',
            'is_recurring'       => 'nullable|boolean',
            'recurrence_pattern' => 'nullable|string|max:50',
        ]);

        // Checkboxes are absent from the request when unchecked — force a proper boolean
        $validated['is_all_day']   = $request->boolean('is_all_day');
        $validated['is_recurring'] = $request->boolean('is_recurring');

        // Handle visibility logic
        if ($validated['visibility'] === 'specific_class' && empty($validated['class_id'])) {
            return back()
                ->withErrors(['class_id' => 'Class is required when visibility is set to specific class.'])
                ->withInput();
        }

        $event->update($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * Calendar view of events.
     */
    public function calendar()
    {
        $academicYears = AcademicYear::where('status', '!=', 'closed')
            ->orderBy('year_name', 'desc')
            ->get();

        return view('admin.events.calendar', compact('academicYears'));
    }

    /**
     * Get events for a specific date range (for AJAX/FullCalendar calls).
     */
    public function getEvents(Request $request)
    {
        $start = $request->get('start');
        $end   = $request->get('end');

        $events = Event::with(['classModel'])
            ->whereBetween('start_datetime', [$start, $end])
            ->orderBy('start_datetime')
            ->get();

        $formattedEvents = $events->map(function ($event) {
            return [
                'id'            => $event->id,
                'title'         => $event->title,
                'start'         => $event->start_datetime->toIso8601String(),
                'end'           => $event->end_datetime?->toIso8601String(),
                'allDay'        => $event->is_all_day,
                'className'     => 'event-' . $event->type,
                'extendedProps' => [
                    'type'        => $event->type,
                    'description' => $event->description,
                    'visibility'  => $event->visibility,
                ],
            ];
        });

        return response()->json($formattedEvents);
    }

    /**
     * Add a comment to an event.
     */
    public function addComment(Request $request, Event $event)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        EventComment::create([
            'event_id'         => $event->id,
            'user_id'          => Auth::id(),
            'comment'          => $validated['comment'],
            'is_admin_comment' => true,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(EventComment $comment)
    {
        if (Auth::user()->role !== 'admin' && $comment->user_id !== Auth::id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }
}
