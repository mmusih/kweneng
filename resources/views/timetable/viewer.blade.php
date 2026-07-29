<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-2xl bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] p-6 shadow-md">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#D3D9D4]">Daily learning plan</p>
            <h2 class="mt-1 text-2xl font-semibold text-white">{{ $title }}</h2>
            @if ($schedule['template'])
                <p class="mt-1 text-sm text-[#D3D9D4]">
                    {{ $schedule['template']['name'] }} · {{ $schedule['template']['academic_year'] }}
                </p>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($children->isNotEmpty())
            <form method="GET" class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <label class="text-sm font-medium text-gray-700">
                    View child
                    <select name="student_id" onchange="this.form.submit()" class="ml-2 rounded-lg border-gray-300 text-sm">
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected($selectedStudent?->id === $child->id)>
                                {{ $child->user?->name }} · {{ $child->currentClass?->name ?? 'No class' }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </form>
        @endif

        @if (! $schedule['template'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
                <h3 class="text-lg font-semibold text-amber-950">No timetable has been published yet</h3>
                <p class="mt-2 text-sm text-amber-800">The school will publish the active timetable here when it is ready.</p>
            </div>
        @elseif ($schedule['days'] === [])
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center text-gray-600">
                No timetable days are configured.
            </div>
        @else
            <div class="flex gap-2 overflow-x-auto pb-2" role="tablist" aria-label="Timetable days">
                @foreach ($schedule['days'] as $day)
                    <button type="button"
                        data-day-tab="{{ $day['day_number'] }}"
                        class="day-tab whitespace-nowrap rounded-full border px-4 py-2 text-sm font-semibold transition">
                        {{ $day['name'] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-4">
                @foreach ($schedule['days'] as $day)
                    <section data-day-panel="{{ $day['day_number'] }}" class="day-panel hidden space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-gray-900">{{ $day['name'] }}</h3>
                            @if ($schedule['selected_day_number'] === $day['day_number'])
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Today</span>
                            @endif
                        </div>

                        @forelse ($day['blocks'] as $block)
                            @php
                                $isLesson = $block['kind'] === 'lesson';
                                $isEvent = $block['kind'] === 'event';
                            @endphp
                            <article class="grid grid-cols-[78px_1fr] gap-3 rounded-2xl border p-4 shadow-sm
                                {{ $isLesson ? 'border-sky-200 bg-white' : ($isEvent ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50') }}">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $block['start_time'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $block['end_time'] }}</p>
                                    <p class="mt-2 text-xs text-gray-400">{{ $block['duration_minutes'] }} min</p>
                                </div>
                                <div class="min-w-0 border-l border-gray-200 pl-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-wide {{ $isLesson ? 'text-sky-700' : 'text-gray-500' }}">
                                                {{ $block['period_name'] }}
                                                @if (($block['end_period_name'] ?? $block['period_name']) !== $block['period_name'])
                                                    – {{ $block['end_period_name'] }}
                                                @endif
                                            </p>
                                            <h4 class="mt-1 text-base font-semibold text-gray-900">{{ $block['title'] }}</h4>
                                        </div>
                                        @if ($isLesson && $block['room'])
                                            <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700">{{ $block['room'] }}</span>
                                        @endif
                                    </div>
                                    @if ($isLesson)
                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                                            @if ($block['teacher']) <span>Teacher: {{ $block['teacher'] }}</span> @endif
                                            @if ($block['class']) <span>Class: {{ $block['class'] }}</span> @endif
                                            @if ($block['group']) <span>Group: {{ $block['group'] }}</span> @endif
                                        </div>
                                        @if ($block['notes'])
                                            <p class="mt-2 text-sm text-gray-500">{{ $block['notes'] }}</p>
                                        @endif
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                                No periods configured for this day.
                            </div>
                        @endforelse
                    </section>
                @endforeach
            </div>
        @endif
    </div>

    @if ($schedule['template'])
        <script>
            const selectedDay = @json($schedule['selected_day_number'] ?? $schedule['days'][0]['day_number']);
            const activateDay = (dayNumber) => {
                document.querySelectorAll('[data-day-panel]').forEach((panel) => {
                    panel.classList.toggle('hidden', Number(panel.dataset.dayPanel) !== Number(dayNumber));
                });
                document.querySelectorAll('[data-day-tab]').forEach((tab) => {
                    const active = Number(tab.dataset.dayTab) === Number(dayNumber);
                    tab.className = `day-tab whitespace-nowrap rounded-full border px-4 py-2 text-sm font-semibold transition ${
                        active ? 'border-[#124E66] bg-[#124E66] text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-[#124E66]'
                    }`;
                });
            };
            document.querySelectorAll('[data-day-tab]').forEach((tab) => {
                tab.addEventListener('click', () => activateDay(tab.dataset.dayTab));
            });
            activateDay(selectedDay);
        </script>
    @endif
</x-app-layout>
