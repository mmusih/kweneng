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
    @endphp

    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">
                        {{ $canManageEvents ? 'Events Management' : 'Events List' }}
                    </h2>
                    <p class="text-white/80 text-sm mt-1">
                        Calendar events, school activities, meetings, exams and holidays.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route($eventRoutePrefix . '.events.calendar') }}"
                        class="inline-flex items-center px-4 py-2 bg-white/15 border border-white/30 rounded-lg font-semibold text-sm text-white hover:bg-white/25">
                        Calendar View
                    </a>
                    @if ($canManageEvents && Route::has($eventRoutePrefix . '.events.create'))
                        <a href="{{ route($eventRoutePrefix . '.events.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-white text-indigo-700 rounded-lg font-semibold text-sm hover:bg-indigo-50">
                            Create New Event
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 kw-soft-section min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 p-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white kw-panel overflow-hidden border-slate-100">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">All Events</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Holidays marked here are used by the attendance register.
                        </p>
                    </div>
                    @if (! $canManageEvents)
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            Read only
                        </span>
                    @endif
                </div>

                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Visibility</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse($events as $event)
                                @php
                                    $typeTone = match ($event->type) {
                                        'holiday' => 'bg-amber-100 text-amber-800',
                                        'exam' => 'bg-red-100 text-red-800',
                                        'meeting' => 'bg-blue-100 text-blue-800',
                                        'activity' => 'bg-emerald-100 text-emerald-800',
                                        'ceremony' => 'bg-purple-100 text-purple-800',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-slate-900">{{ $event->title }}</div>
                                        @if ($event->description)
                                            <div class="text-sm text-slate-500 truncate max-w-xs">{{ Str::limit($event->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $typeTone }}">
                                            {{ ucfirst($event->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        <div>{{ $event->start_datetime->format('M j, Y') }}</div>
                                        <div class="text-xs">{{ $event->is_all_day ? 'All day' : $event->start_datetime->format('g:i A') }}</div>
                                        @if ($event->end_datetime && ! $event->is_all_day)
                                            <div class="text-xs">to {{ $event->end_datetime->format('g:i A') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        {{ \App\Models\Event::getVisibilityLabel($event->visibility) }}
                                        @if ($event->visibility === 'specific_class' && $event->classModel)
                                            <div class="text-xs">{{ $event->classModel->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($event->start_datetime > now())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Upcoming</span>
                                        @elseif($event->start_datetime <= now() && (!$event->end_datetime || $event->end_datetime >= now()))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Ongoing</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">Completed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route($eventRoutePrefix . '.events.show', $event) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                        @if ($canManageEvents)
                                            <a href="{{ route($eventRoutePrefix . '.events.edit', $event) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                            <form action="{{ route($eventRoutePrefix . '.events.destroy', $event) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this event?')">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">No events found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 pb-6">
                    {{ $events->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
