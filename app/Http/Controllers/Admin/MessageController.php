<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentMessage;
use App\Models\ParentMessageReply;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index()
    {
        $messages = ParentMessage::with(['parent.user', 'latestReply.senderUser'])
            ->orderByRaw('is_read_by_admin ASC')
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(30);

        $unreadCount = ParentMessage::where('is_read_by_admin', false)->count();

        return view('admin.messages.index', compact('messages', 'unreadCount'))->with('messageRoutePrefix', $this->routePrefix());
    }

    public function show(ParentMessage $message)
    {
        $message->update(['is_read_by_admin' => true]);

        $message->load(['parent.user', 'replies.senderUser']);

        return view('admin.messages.show', compact('message'))->with('messageRoutePrefix', $this->routePrefix());
    }

    public function reply(
        Request $request,
        ParentMessage $message,
        FirebaseNotificationService $firebase
    ) {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ParentMessageReply::create([
            'message_id'     => $message->id,
            'sender_role'    => Auth::user()?->role ?? 'admin',
            'sender_user_id' => Auth::id(),
            'body'           => $validated['body'],
        ]);

        $message->update([
            'is_read_by_parent' => false,
            'is_read_by_admin'  => true,
            'last_reply_at'     => now(),
        ]);

        $message->loadMissing('parent');

        if ($message->parent) {
            $firebase->sendToParent(
                $message->parent->id,
                'New Message from School',
                $message->subject ?: 'The school has replied to your message.',
                [
                    'type'       => 'message',
                    'message_id' => $message->id,
                    'screen'     => 'messages',
                ]
            );
        }

        return redirect()->route($this->routePrefix() . '.messages.show', $message)
            ->with('success', 'Reply sent to parent.');
    }


    protected function routePrefix(): string
    {
        return request()->routeIs('office.*') ? 'office' : 'admin';
    }
}
