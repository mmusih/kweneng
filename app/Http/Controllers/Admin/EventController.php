<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Event;
use App\Models\EventComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with(['classModel', 'academicYear', 'creator', 'comments'])
            ->orderBy('start_datetime', 'desc')
            ->paginate(20);

        return view('admin.events.index', [
            'events' => $events,
            'eventRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function create()
    {
        $classes = ClassModel::with('academicYear')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::where('status', '!=', 'closed')
            ->orderBy('year_name', 'desc')
            ->get();

        return view('admin.events.create', [
            'classes' => $classes,
            'academicYears' => $academicYears,
            'eventRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEvent($request);

        $validated['is_all_day'] = $request->boolean('is_all_day');
        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['created_by'] = Auth::id();
        $validated['created_by_role'] = Auth::user()->role;

        $validated = $this->normaliseEventData($validated);

        Event::create($validated);

        return redirect()->route($this->routePrefix() . '.events.index')
            ->with('success', $validated['type'] === Event::TYPE_HOLIDAY
                ? 'Holiday saved successfully. Attendance registers will show this date as a holiday.'
                : 'Event created successfully.');
    }

    public function show(Event $event)
    {
        $event->load(['classModel', 'academicYear', 'creator', 'comments.user']);

        return view('admin.events.show', [
            'event' => $event,
            'eventRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function edit(Event $event)
    {
        $classes = ClassModel::with('academicYear')
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::where('status', '!=', 'closed')
            ->orderBy('year_name', 'desc')
            ->get();

        return view('admin.events.edit', [
            'event' => $event,
            'classes' => $classes,
            'academicYears' => $academicYears,
            'eventRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $this->validateEvent($request);

        $validated['is_all_day'] = $request->boolean('is_all_day');
        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated = $this->normaliseEventData($validated);

        $event->update($validated);

        return redirect()->route($this->routePrefix() . '.events.index')
            ->with('success', $validated['type'] === Event::TYPE_HOLIDAY
                ? 'Holiday updated successfully. Attendance registers will reflect the calendar change.'
                : 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route($this->routePrefix() . '.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function calendar()
    {
        $academicYears = AcademicYear::where('status', '!=', 'closed')
            ->orderBy('year_name', 'desc')
            ->get();

        return view('admin.events.calendar', [
            'academicYears' => $academicYears,
            'eventRoutePrefix' => $this->routePrefix(),
        ]);
    }

    public function getEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $events = Event::with(['classModel'])
            ->whereBetween('start_datetime', [$start, $end])
            ->orderBy('start_datetime')
            ->get();

        $formattedEvents = $events->map(function (Event $event) {
            return [
                'id' => $event->id,
                'title' => $event->type === Event::TYPE_HOLIDAY ? 'Holiday: ' . $event->title : $event->title,
                'start' => $event->start_datetime->toIso8601String(),
                'end' => $event->end_datetime?->toIso8601String(),
                'allDay' => $event->is_all_day,
                'className' => 'event-' . $event->type,
                'backgroundColor' => $event->type === Event::TYPE_HOLIDAY ? '#f59e0b' : null,
                'borderColor' => $event->type === Event::TYPE_HOLIDAY ? '#d97706' : null,
                'extendedProps' => [
                    'type' => $event->type,
                    'description' => $event->description,
                    'visibility' => $event->visibility,
                    'attendance_affects' => $event->type === Event::TYPE_HOLIDAY,
                ],
            ];
        });

        return response()->json($formattedEvents);
    }

    public function addComment(Request $request, Event $event)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        EventComment::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
            'is_admin_comment' => true,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    public function deleteComment(EventComment $comment)
    {
        if (! in_array(Auth::user()->role, ['admin', 'headmaster', 'office', 'register_officer'], true) && $comment->user_id !== Auth::id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['nullable', 'date', 'after_or_equal:start_datetime'],
            'type' => ['required', 'in:holiday,exam,meeting,activity,ceremony,other'],
            'visibility' => ['required', 'in:all,parents,teachers,students,specific_class'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'is_all_day' => ['nullable', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_pattern' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function normaliseEventData(array $validated): array
    {
        if ($validated['visibility'] === 'specific_class' && empty($validated['class_id'])) {
            throw ValidationException::withMessages([
                'class_id' => 'Class is required when visibility is set to specific class.',
            ]);
        }

        if ($validated['visibility'] !== 'specific_class') {
            $validated['class_id'] = null;
        }

        if ($validated['type'] === Event::TYPE_HOLIDAY) {
            $validated['is_all_day'] = true;
            $validated['is_recurring'] = false;
            $validated['recurrence_pattern'] = null;
        }

        return $validated;
    }

    private function routePrefix(): string
    {
        return match (true) {
            request()->routeIs('headmaster.*') => 'headmaster',
            request()->routeIs('office.*') => 'office',
            request()->routeIs('register-officer.*') => 'register-officer',
            default => 'admin',
        };
    }
}
