<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-800">{{ $message->subject }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Started {{ $message->created_at->format('M j, Y') }}</p>
                </div>
                <a href="{{ route('parent.messages.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Original message --}}
            <div class="bg-white rounded-lg shadow-sm p-5 border-l-4" style="border-color:#2C3E6B;">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }} <span
                            class="font-normal text-gray-400">(You)</span></p>
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
                                {{ $reply->senderUser->name ?? 'School Admin' }}
                                <span class="font-normal text-indigo-400 text-xs ml-1">School Administration</span>
                            </p>
                            <p class="text-xs text-indigo-400">{{ $reply->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <p class="text-sm text-indigo-900 whitespace-pre-wrap">{{ $reply->body }}</p>
                    </div>
                @else
                    <div class="bg-white rounded-lg shadow-sm p-5 border-l-4" style="border-color:#2C3E6B;">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }} <span
                                    class="font-normal text-gray-400">(You)</span></p>
                            <p class="text-xs text-gray-400">{{ $reply->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $reply->body }}</p>
                    </div>
                @endif
            @endforeach

            {{-- Reply form --}}
            <div class="bg-white rounded-lg shadow-sm p-5">
                <p class="text-sm font-semibold text-gray-700 mb-3">Reply</p>
                <form action="{{ route('parent.messages.reply', $message) }}" method="POST" class="space-y-3">
                    @csrf
                    <textarea name="body" rows="4"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="Write your reply..." required maxlength="5000">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white transition"
                            style="background:#2C3E6B;" onmouseover="this.style.background='#1e2d4f'"
                            onmouseout="this.style.background='#2C3E6B'">
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
