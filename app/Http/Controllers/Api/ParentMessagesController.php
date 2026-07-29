<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentMessage;
use Illuminate\Http\Request;

class ParentMessagesController extends Controller
{
    /**
     * GET /api/parent/messages
     *
     * Returns all parent message threads plus an unread counter.
     * A thread is unread for the parent when is_read_by_parent is false.
     */
    public function index(Request $request)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $messages = ParentMessage::with(['replies'])
            ->where('parent_id', $parent->id)
            ->orderByRaw('is_read_by_parent ASC')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->get();

        $formatted = $messages->map(fn ($m) => $this->formatThread($m))->values();

        return response()->json([
            // "messages" is what the current Flutter provider expects.
            'messages'     => $formatted,

            // "threads" is kept for backward compatibility with older Flutter code.
            'threads'      => $formatted,

            'unread_count' => $messages->where('is_read_by_parent', false)->count(),
        ]);
    }

    /**
     * GET /api/parent/messages/{id}
     *
     * Opening a thread automatically marks it as read for this parent.
     */
    public function show(Request $request, $id)
    {
        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $message = ParentMessage::with(['replies'])
            ->where('parent_id', $parent->id)
            ->findOrFail($id);

        if (! $message->is_read_by_parent) {
            $message->update([
                'is_read_by_parent' => true,
            ]);
        }

        $message->refresh()->load(['replies']);

        return response()->json([
            'message' => $this->formatThread($message),
        ]);
    }

    /**
     * POST /api/parent/messages
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $message = ParentMessage::create([
            'parent_id'         => $parent->id,
            'subject'           => $request->subject,
            'body'              => $request->body,
            'is_read_by_admin'  => false,
            'is_read_by_parent' => true,
            'last_reply_at'     => now(),
        ]);

        return response()->json([
            'message' => $this->formatThread($message->load(['replies'])),
        ], 201);
    }

    /**
     * POST /api/parent/messages/{id}/reply
     */
    public function reply(Request $request, $id)
    {
        $request->validate([
            'body' => ['required', 'string'],
        ]);

        $parent = $request->user()->parent;

        if (! $parent) {
            return response()->json(['message' => 'Parent profile not found.'], 404);
        }

        $message = ParentMessage::where('parent_id', $parent->id)->findOrFail($id);

        $reply = $message->replies()->create([
            'body'             => $request->body,
            'sender_role'      => 'parent',
            'sender_user_id'   => $request->user()->id,
            'sent_at'          => now(),
        ]);

        $message->update([
            'is_read_by_admin'  => false,
            'is_read_by_parent' => true,
            'last_reply_at'     => now(),
        ]);

        return response()->json([
            'id'          => $reply->id,
            'body'        => $reply->body,
            'sender_role' => $reply->sender_role,
            'sent_at'     => $reply->sent_at?->toDateTimeString(),
        ], 201);
    }

    private function formatThread(ParentMessage $m): array
    {
        $replies = $m->replies ?? collect();

        return [
            'id'                => $m->id,
            'subject'           => $m->subject,
            'body'              => $m->body,
            'is_read_by_parent' => (bool) $m->is_read_by_parent,
            'is_read_by_admin'  => (bool) $m->is_read_by_admin,
            'has_unread'        => ! (bool) $m->is_read_by_parent,
            'last_reply_at'     => $m->last_reply_at?->toDateTimeString()
                ?? $replies->last()?->sent_at?->toDateTimeString(),
            'created_at'        => $m->created_at->toDateTimeString(),
            'replies'           => $replies->map(fn ($r) => [
                'id'          => $r->id,
                'body'        => $r->body,
                'sender_role' => $r->sender_role,
                'sent_at'     => $r->sent_at?->toDateTimeString(),
            ])->values(),
        ];
    }
}
