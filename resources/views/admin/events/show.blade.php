<x-app-layout>
    @php
        $eventRoutePrefix = $eventRoutePrefix ?? match (true) {
            request()->routeIs('headmaster.*') => 'headmaster',
            request()->routeIs('office.*') => 'office',
            request()->routeIs('register-officer.*') => 'register-officer',
            request()->routeIs('inventory.*') => 'inventory',
            default => 'admin',
        };
        $canManageEvents = in_array($eventRoutePrefix, ['admin', 'headmaster', 'office', 'register-officer'], true);
        $canComment = $canManageEvents && Route::has($eventRoutePrefix . '.events.add-comment');
    @endphp

    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">Event Details</h2>
                    <p class="text-white/80 text-sm mt-1">{{ $event->title }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($canManageEvents && Route::has($eventRoutePrefix . '.events.edit'))
                        <a href="{{ route($eventRoutePrefix . '.events.edit', $event) }}"
                            class="inline-flex items-center px-4 py-2 bg-white text-indigo-700 rounded-lg font-semibold text-sm hover:bg-indigo-50">
                            Edit Event
                        </a>
                    @endif
                    <a href="{{ route($eventRoutePrefix . '.events.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white/15 border border-white/30 rounded-lg font-semibold text-sm text-white hover:bg-white/25">
                        Back to Events
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 kw-soft-section min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white kw-panel overflow-hidden border-slate-100">
                <div class="p-6 bg-white border-b border-slate-100">
                    <div class="mb-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-slate-900">{{ $event->title }}</h1>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ \App\Models\Event::getTypeColor($event->type) }}-100 text-{{ \App\Models\Event::getTypeColor($event->type) }}-800">
                                        {{ \App\Models\Event::getTypeLabel($event->type) }}
                                    </span>
                                    <span class="text-sm text-slate-500">
                                        {{ \App\Models\Event::getVisibilityLabel($event->visibility) }}
                                    </span>
                                    @if (! $canManageEvents)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">Read only</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-slate-500">Created by</div>
                                <div class="font-medium text-slate-900">{{ $event->creator->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-500">{{ $event->created_at->format('M j, Y') }}</div>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <h3 class="text-sm font-semibold text-slate-900">Date & Time</h3>
                                <div class="mt-2 text-sm text-slate-600 space-y-1">
                                    <div><strong>Start:</strong> {{ $event->start_datetime->format('M j, Y g:i A') }}</div>
                                    @if ($event->end_datetime)
                                        <div><strong>End:</strong> {{ $event->end_datetime->format('M j, Y g:i A') }}</div>
                                    @endif
                                    @if ($event->is_all_day)
                                        <div><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">All Day Event</span></div>
                                    @endif
                                </div>
                            </div>

                            @if ($event->classModel || $event->academicYear)
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <h3 class="text-sm font-semibold text-slate-900">Target Audience</h3>
                                    <div class="mt-2 text-sm text-slate-600 space-y-1">
                                        @if ($event->academicYear)
                                            <div><strong>Academic Year:</strong> {{ $event->academicYear->year_name }}</div>
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
                                <h3 class="text-sm font-semibold text-slate-900">Description</h3>
                                <div class="mt-2 text-slate-600 prose max-w-none">{!! nl2br(e($event->description)) !!}</div>
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-slate-200 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-slate-900">Comments ({{ $event->comments->count() }})</h3>
                        </div>

                        @if ($canComment)
                            <form method="POST" action="{{ route($eventRoutePrefix . '.events.add-comment', $event) }}" class="mb-6">
                                @csrf
                                <div class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-indigo-800 font-semibold">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <label for="comment" class="sr-only">Add a comment</label>
                                        <textarea id="comment" name="comment" rows="3" class="shadow-sm block w-full focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm border border-slate-300 rounded-md" placeholder="Add a comment..." required></textarea>
                                        <div class="mt-3">
                                            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                                Post Comment
                                            </button>
                                        </div>
                                        @error('comment')
                                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="mb-6 rounded-lg bg-slate-50 border border-slate-200 text-slate-600 p-3 text-sm">
                                Comments are read only for this role.
                            </div>
                        @endif

                        @if ($event->comments->count() > 0)
                            <div class="space-y-6">
                                @foreach ($event->comments->sortByDesc('created_at') as $comment)
                                    <div class="flex space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center">
                                                <span class="text-slate-800 font-semibold">{{ strtoupper(substr($comment->user->name, 0, 1)) }}</span>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <span class="text-sm font-semibold text-slate-900">{{ $comment->user->name }}</span>
                                                    @if ($comment->is_admin_comment)
                                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">Admin</span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-slate-500">{{ $comment->created_at->format('M j, Y g:i A') }}</div>
                                            </div>
                                            <div class="mt-1 text-sm text-slate-700">{!! nl2br(e($comment->comment)) !!}</div>
                                            @if ($canComment && ($comment->user_id === Auth::id() || Auth::user()->role === 'admin'))
                                                <div class="mt-2">
                                                    <form action="{{ route($eventRoutePrefix . '.events.delete-comment', $comment) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this comment?')">
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
                            <div class="text-center py-6 text-sm text-slate-500">No comments yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
