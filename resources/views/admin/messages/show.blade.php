<x-app-layout>
    @php($messageRoutePrefix = $messageRoutePrefix ?? (request()->routeIs('office.*') ? 'office' : 'admin'))
    <x-slot name="header">
        <div class="mt-16 p-5 rounded-2xl kw-page-header flex items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-2xl text-white">{{ $message->subject }}</h2>
                <p class="text-sm text-white/80 mt-0.5">
                    From {{ $message->parent->user->name ?? 'Unknown Parent' }}
                    &middot; {{ $message->created_at->format('M j, Y') }}
                </p>
            </div>
            <a href="{{ route($messageRoutePrefix . '.messages.index') }}"
                class="inline-flex items-center px-4 py-2 bg-white text-slate-800 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-slate-100 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Inbox
            </a>
        </div>
    </x-slot>

    <div class="py-12 kw-soft-section min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Parent info strip --}}
            <div class="bg-white rounded-lg shadow-sm p-4 flex items-center gap-3">
                <div class="h-10 w-10 rounded-full flex items-center justify-center font-semibold text-white text-sm flex-shrink-0"
                    style="background:#2C3E6B;">
                    {{ strtoupper(substr($message->parent->user->name ?? 'P', 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $message->parent->user->name ?? 'Unknown Parent' }}</p>
                    <p class="text-xs text-gray-400">{{ $message->parent->user->email ?? '' }} &middot;
                        {{ $message->parent->phone ?? 'No phone' }}</p>
                </div>
            </div>

            {{-- Original message --}}
            <div class="bg-gray-50 rounded-lg shadow-sm p-5 border-l-4 border-gray-400">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-gray-700">{{ $message->parent->user->name ?? 'Parent' }}</p>
                    <p class="text-xs text-gray-400">{{ $message->created_at->format('M j, Y g:i A') }}</p>
                </div>
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $message->body }}</p>
            </div>

            {{-- Replies --}}
            @foreach ($message->replies as $reply)
                @if ($reply->sender_role === 'admin')
                    <div class="bg-indigo-50 rounded-lg shadow-sm p-5 border-l-4 border-indigo-400">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-indigo-800">
                                {{ $reply->senderUser->name ?? 'Admin' }}
                                <span class="font-normal text-indigo-400 text-xs ml-1">(You)</span>
                            </p>
                            <p class="text-xs text-indigo-400">{{ $reply->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <p class="text-sm text-indigo-900 whitespace-pre-wrap">{{ $reply->body }}</p>
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg shadow-sm p-5 border-l-4 border-gray-400">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-700">
                                {{ $message->parent->user->name ?? 'Parent' }}</p>
                            <p class="text-xs text-gray-400">{{ $reply->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $reply->body }}</p>
                    </div>
                @endif
            @endforeach

            {{-- Admin reply form --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-sm font-semibold text-gray-700 mb-3">Reply to Parent</p>
                <form action="{{ route($messageRoutePrefix . '.messages.reply', $message) }}" method="POST" class="space-y-3">
                    @csrf
                    <textarea name="body" rows="5"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="Write your reply to the parent..." required maxlength="5000">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
