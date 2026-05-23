<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the announcements.
     */
    public function index()
    {
        $announcements = Announcement::with(['author', 'classModel', 'subject'])
            ->latest()
            ->paginate(20);

        return view('admin.announcements.index', compact('announcements'));
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create()
    {
        $classes = ClassModel::all();
        $subjects = Subject::all();

        return view('admin.announcements.create', compact('classes', 'subjects'));
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'type'         => 'required|in:general,academic,event,urgent',
            'audience'     => 'required|in:all,parents,teachers,students,specific_class,specific_subject',
            'class_id'     => 'nullable|exists:classes,id',
            'subject_id'   => 'nullable|exists:subjects,id',
            'publish_at'   => 'nullable|date',
            'expires_at'   => 'nullable|date|after:publish_at',
            // is_published removed from here
        ]);

        $validated['is_published'] = $request->boolean('is_published');
        $validated['author_id']    = Auth::id();
        $validated['author_role']  = 'admin';

        Announcement::create($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    /**
     * Display the specified announcement.
     */
    public function show(Announcement $announcement)
    {
        $announcement->load(['author', 'classModel', 'subject']);
        return view('admin.announcements.show', compact('announcement'));
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit(Announcement $announcement)
    {
        $classes = ClassModel::all();
        $subjects = Subject::all();

        return view('admin.announcements.edit', compact('announcement', 'classes', 'subjects'));
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'type'         => 'required|in:general,academic,event,urgent',
            'audience'     => 'required|in:all,parents,teachers,students,specific_class,specific_subject',
            'class_id'     => 'nullable|exists:classes,id',
            'subject_id'   => 'nullable|exists:subjects,id',
            'publish_at'   => 'nullable|date',
            'expires_at'   => 'nullable|date|after:publish_at',
            // is_published removed from here
        ]);

        $validated['is_published'] = $request->boolean('is_published');

        $announcement->update($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }
}
