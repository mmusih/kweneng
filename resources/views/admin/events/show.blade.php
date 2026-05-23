<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Event Details
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.events.edit', $event) }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Edit Event
                </a>
                <a href="{{ route('admin.events.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Back to Events
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Event Details -->
                    <div class="mb-8">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ $event->title }}</h1>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ \App\Models\Event::getTypeColor($event->type) }}-100 text-{{ \App\Models\Event::getTypeColor($event->type) }}-800">
                                        {{ \App\Models\Event::getTypeLabel($event->type) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ \App\Models\Event::getVisibilityLabel($event->visibility) }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Created by</div>
                                <div class="font-medium">{{ $event->creator->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500">{{ $event->created_at->format('M j, Y') }}</div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Date & Time</h3>
                                <div class="mt-2 text-sm text-gray-500">
                                    <div><strong>Start:</strong> {{ $event->start_datetime->format('M j, Y g:i A') }}
                                    </div>
                                    @if ($event->end_datetime)
                                        <div><strong>End:</strong> {{ $event->end_datetime->format('M j, Y g:i A') }}
                                        </div>
                                    @endif
                                    @if ($event->is_all_day)
                                        <div class="mt-1"><span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">All
                                                Day Event</span></div>
                                    @endif
                                </div>
                            </div>

                            @if ($event->classModel || $event->academicYear)
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900">Target Audience</h3>
                                    <div class="mt-2 text-sm text-gray-500">
                                        @if ($event->academicYear)
                                            <div><strong>Academic Year:</strong> {{ $event->academicYear->year_name }}
                                            </div>
                                        @endif
                                        @if ($event->classModel)
                                            <div><strong>Class:</strong> {{ $event->classModel->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($event->description)
                            <div class="mt-6">
                                <h3 class="text-sm font-medium text-gray-900">Description</h3>
                                <div class="mt-2 text-gray-600 prose max-w-none">
                                    {!! nl2br(e($event->description)) !!}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Comments Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Comments ({{ $event->comments->count() }})
                            </h3>
                        </div>

                        <!-- Add Comment Form -->
                        <form method="POST" action="{{ route('admin.events.add-comment', $event) }}" class="mb-6">
                            @csrf
                            <div class="flex space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <span
                                            class="text-indigo-800 font-medium">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div>
                                        <label for="comment" class="sr-only">Add a comment</label>
                                        <textarea id="comment" name="comment" rows="3"
                                            class="shadow-sm block w-full focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border border-gray-300 rounded-md"
                                            placeholder="Add a comment..." required></textarea>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Post Comment
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @error('comment')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </form>

                        <!-- Comments List -->
                        @if ($event->comments->count() > 0)
                            <div class="space-y-6">
                                @foreach ($event->comments->sortByDesc('created_at') as $comment)
                                    <div class="flex space-x-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <span
                                                    class="text-gray-800 font-medium">{{ strtoupper(substr($comment->user->name, 0, 1)) }}</span>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span
                                                        class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</span>
                                                    @if ($comment->is_admin_comment)
                                                        <span
                                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                            Admin
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $comment->created_at->format('M j, Y g:i A') }}
                                                </div>
                                            </div>
                                            <div class="mt-1 text-sm text-gray-700">
                                                {!! nl2br(e($comment->comment)) !!}
                                            </div>
                                            @if ($comment->user_id === Auth::id() || Auth::user()->role === 'admin')
                                                <div class="mt-2 flex space-x-2">
                                                    <form action="{{ route('admin.events.delete-comment', $comment) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="text-xs text-red-600 hover:text-red-800"
                                                            onclick="return confirm('Are you sure you want to delete this comment?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                    </path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No comments yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Be the first to add a comment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
