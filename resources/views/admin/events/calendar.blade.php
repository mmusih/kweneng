<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Events Calendar
            </h2>
            <a href="{{ route('admin.events.create') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Create New Event
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include FullCalendar CSS and JS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: '{{ route('admin.events.get-events') }}',
                    method: 'GET',
                    failure: function() {
                        alert('There was an error fetching events!');
                    }
                },
                eventClick: function(info) {
                    // Show event details in a modal or redirect to event page
                    window.location.href = '/admin/events/' + info.event.id;
                },
                eventContent: function(arg) {
                    // Customize event display
                    let italicEl = document.createElement('div');
                    italicEl.innerHTML = '<b>' + arg.event.title + '</b>';

                    let arrayOfDomNodes = [italicEl];
                    return {
                        domNodes: arrayOfDomNodes
                    };
                }
            });

            calendar.render();
        });
    </script>
</x-app-layout>
