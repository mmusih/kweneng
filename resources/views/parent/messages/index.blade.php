<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Messages</h2>
            <div class="flex gap-2">
                {{-- New Message Button --}}
                <a href="{{ route('parent.messages.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Message
                </a>

                {{-- Back to Dashboard Link (Copied from Announcements) --}}
                <a href="{{ route('parent.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    ← Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
                @forelse ($messages as $message)
                    <a href="{{ route('parent.messages.show', $message) }}"
                        class="flex items-start gap-4 p-5 border-b border-gray-100 hover:bg-gray-50 transition {{ !$message->is_read_by_parent ? 'bg-indigo-50' : '' }}">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center font-semibold text-white text-sm"
                            style="background:#2C3E6B;">
                            S
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p
                                    class="text-sm font-{{ !$message->is_read_by_parent ? 'bold' : 'medium' }} text-gray-900 truncate">
                                    {{ $message->subject }}
                                </p>
                                @if (!$message->is_read_by_parent)
                                    <span
                                        class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                        New reply
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                @if ($message->latestReply)
                                    {{ $message->latestReply->senderUser->name ?? 'Admin' }}:
                                    {{ Str::limit($message->latestReply->body, 80) }}
                                @else
                                    {{ Str::limit($message->body, 80) }}
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-xs text-gray-400 whitespace-nowrap">
                            {{ ($message->last_reply_at ?? $message->created_at)->format('M j') }}
                        </div>
                    </a>
                @empty
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <p class="mt-3 text-gray-500 text-sm">No messages yet.</p>
                        <a href="{{ route('parent.messages.create') }}" class="mt-2 inline-block text-sm font-medium"
                            style="color:#2C3E6B;">
                            Send your first message →
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">{{ $messages->links() }}</div>
        </div>
    </div>
</x-app-layout>
