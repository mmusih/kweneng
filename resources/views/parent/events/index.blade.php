<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Events</h2>
            <div class="flex gap-2">
                <a href="{{ route('parent.announcements.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    📢 Announcements
                </a>
                <a href="{{ route('parent.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    ← Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- FullCalendar --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-sm font-semibold text-gray-700 mb-4">📅 Calendar</p>
                <div id="calendar"></div>
            </div>

            {{-- Upcoming Events List --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <p class="text-sm font-semibold text-gray-700 mb-4">Upcoming Events</p>

                @forelse($upcomingEvents as $event)
                    <div class="flex items-start gap-4 py-3 border-b border-gray-100 last:border-0">
                        {{-- Date badge --}}
                        <div class="flex-shrink-0 w-12 text-center">
                            <div class="text-xs font-semibold uppercase text-purple-600">
                                {{ $event->start_datetime->format('M') }}
                            </div>
                            <div class="text-2xl font-bold text-gray-900 leading-none">
                                {{ $event->start_datetime->format('d') }}
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-medium text-gray-900">{{ $event->title }}</p>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    bg-{{ \App\Models\Event::getTypeColor($event->type) }}-100
                                    text-{{ \App\Models\Event::getTypeColor($event->type) }}-800">
                                    {{ \App\Models\Event::getTypeLabel($event->type) }}
                                </span>
                                @if ($event->is_all_day)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        All Day
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if ($event->is_all_day)
                                    All day
                                @else
                                    {{ $event->start_datetime->format('g:i A') }}
                                    @if ($event->end_datetime)
                                        – {{ $event->end_datetime->format('g:i A') }}
                                    @endif
                                @endif
                                @if ($event->end_datetime && !$event->start_datetime->isSameDay($event->end_datetime))
                                    · ends {{ $event->end_datetime->format('M d, Y') }}
                                @endif
                            </p>
                            @if ($event->description)
                                <p class="text-xs text-gray-400 mt-1">{{ Str::limit($event->description, 100) }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-400 text-sm">No upcoming events scheduled.</p>
                    </div>
                @endforelse
            </div>

            {{-- Past Events --}}
            @if ($pastEvents->count() > 0)
                <div class="bg-white rounded-xl border border-gray-200 p-5">
                    <p class="text-sm font-semibold text-gray-400 mb-4">Past Events</p>
                    <div class="space-y-2">
                        @foreach ($pastEvents as $event)
                            <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0 opacity-60">
                                <div class="flex-shrink-0 w-12 text-center">
                                    <div class="text-xs text-gray-400">{{ $event->start_datetime->format('M') }}</div>
                                    <div class="text-lg font-bold text-gray-500">
                                        {{ $event->start_datetime->format('d') }}</div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-600 truncate">{{ $event->title }}</p>
                                    <p class="text-xs text-gray-400">{{ $event->start_datetime->format('Y') }}</p>
                                </div>
                                <span class="text-xs text-gray-400 px-2 py-0.5 rounded-full bg-gray-100">
                                    {{ \App\Models\Event::getTypeLabel($event->type) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- FullCalendar --}}
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

    <style>
        /* Color coding for event types */
        .event-holiday {
            background-color: #d97706 !important;
            border-color: #b45309 !important;
        }

        .event-exam {
            background-color: #dc2626 !important;
            border-color: #b91c1c !important;
        }

        .event-meeting {
            background-color: #2563eb !important;
            border-color: #1d4ed8 !important;
        }

        .event-activity {
            background-color: #16a34a !important;
            border-color: #15803d !important;
        }

        .event-ceremony {
            background-color: #7c3aed !important;
            border-color: #6d28d9 !important;
        }

        .event-other {
            background-color: #4b5563 !important;
            border-color: #374151 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                events: {
                    url: '{{ route('parent.events.calendar-data') }}',
                    method: 'GET',
                    failure: function() {
                        console.error('Failed to load events');
                    }
                },
                eventClick: function(info) {
                    const p = info.event.extendedProps;
                    alert(
                        info.event.title + '\n' +
                        'Type: ' + p.type + '\n' +
                        (p.description ? 'Details: ' + p.description : '')
                    );
                },
                height: 'auto',
            });
            calendar.render();
        });
    </script>
</x-app-layout>
