<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Mark an announcement as dismissed (read) for the authenticated parent.
     * Called via AJAX from the dashboard.
     *
     * POST /parent/announcements/{announcement}/dismiss
     */
    public function dismiss(Announcement $announcement)
    {
        $parent = Auth::user()->parent;

        if (! $parent) {
            return response()->json(['error' => 'Parent not found'], 403);
        }

        $announcement->markReadByParent($parent);

        return response()->json(['success' => true]);
    }

    /**
     * Mark ALL announcements as read when the parent visits the announcements page.
     * Called by EventsController::announcements() — see that method.
     */
    public function markAllRead(): void
    {
        $parent = Auth::user()?->parent;

        if (! $parent) {
            return;
        }

        // Fetch published parent announcements not yet read
        $unread = Announcement::published()
            ->forParents()
            ->unreadByParent($parent->id)
            ->get()
            ->filter(fn($a) => $a->isRelevantToParent($parent));

        foreach ($unread as $announcement) {
            $announcement->markReadByParent($parent);
        }
    }
}
