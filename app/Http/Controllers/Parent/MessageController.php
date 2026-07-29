<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\ParentMessage;
use App\Models\ParentMessageReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * List all message threads for the authenticated parent.
     */
    public function index()
    {
        $parent = Auth::user()->parent;

        $messages = ParentMessage::where('parent_id', $parent->id)
            ->with('latestReply.senderUser')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('parent.messages.index', compact('messages'));
    }

    /**
     * Show the compose new message form.
     */
    public function create()
    {
        return view('parent.messages.create');
    }

    /**
     * Store a new message thread.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:5000'],
        ]);

        $parent = Auth::user()->parent;

        ParentMessage::create([
            'parent_id'          => $parent->id,
            'subject'            => $validated['subject'],
            'body'               => $validated['body'],
            'is_read_by_admin'   => false,
            'is_read_by_parent'  => true,
        ]);

        return redirect()->route('parent.messages.index')
            ->with('success', 'Your message has been sent to the school.');
    }

    /**
     * Show a message thread and all its replies.
     */
    public function show(ParentMessage $message)
    {
        $parent = Auth::user()->parent;

        // Ensure this message belongs to the authenticated parent
        abort_unless($message->parent_id === $parent->id, 403);

        // Mark as read by parent (clears the unread badge)
        $message->update(['is_read_by_parent' => true]);

        $message->load('replies.senderUser');

        return view('parent.messages.show', compact('message'));
    }

    /**
     * Add a reply to an existing thread.
     */
    public function reply(Request $request, ParentMessage $message)
    {
        $parent = Auth::user()->parent;

        abort_unless($message->parent_id === $parent->id, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ParentMessageReply::create([
            'message_id'     => $message->id,
            'sender_role'    => 'parent',
            'sender_user_id' => Auth::id(),
            'body'           => $validated['body'],
        ]);

        // Reset admin read flag so admin sees the new reply
        $message->update([
            'is_read_by_admin' => false,
            'last_reply_at'    => now(),
        ]);

        return redirect()->route('parent.messages.show', $message)
            ->with('success', 'Reply sent.');
    }
}
