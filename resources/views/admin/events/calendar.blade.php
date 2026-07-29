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
                    <h2 class="font-semibold text-2xl text-white leading-tight">Events Calendar</h2>
                    <p class="text-white/80 text-sm mt-1">School calendar, holidays and operational dates.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route($eventRoutePrefix . '.events.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-white/15 border border-white/30 rounded-lg font-semibold text-sm text-white hover:bg-white/25">
                        Events List
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white kw-panel overflow-hidden border-slate-100">
                <div class="p-6 bg-white border-b border-slate-100">
                    @if (! $canManageEvents)
                        <div class="mb-4 rounded-lg bg-slate-50 border border-slate-200 text-slate-700 p-3 text-sm">
                            This calendar is read only for this role.
                        </div>
                    @endif
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: '{{ route($eventRoutePrefix . '.events.get-events') }}',
                    method: 'GET',
                    failure: function() {
                        alert('There was an error fetching events.');
                    }
                },
                eventClick: function(info) {
                    window.location.href = '{{ url($eventRoutePrefix . '/events') }}/' + info.event.id;
                },
                eventContent: function(arg) {
                    const title = document.createElement('div');
                    title.innerHTML = '<b>' + arg.event.title + '</b>';
                    return { domNodes: [title] };
                }
            });

            calendar.render();
        });
    </script>
</x-app-layout>
