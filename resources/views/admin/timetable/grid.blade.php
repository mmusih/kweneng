{{--
    The drag-and-drop timetable editor.

    One fixed-height CSS grid row per class, with columns grouped day-by-day. Drop targets
    sit underneath the cards. Parallel option cards are fitted within the same class row;
    they never create additional class rows.

    The server is the authority on every move. `candidates` only tells us what to paint.
--}}
@php
    // Prefill the day-structure form from the timetable as it stands, so opening the
    // panel and pressing Apply is a no-op rather than a surprise.
    $firstPeriod = $grid['periods'][0] ?? null;
    $secondPeriod = $grid['periods'][1] ?? null;

    $periodMinutes = 40;

    if ($firstPeriod) {
        $periodMinutes = max(5, (int) round(
            (strtotime($firstPeriod['end']) - strtotime($firstPeriod['start'])) / 60
        ));
    }

    $breakDefaults = collect($grid['breaks'])->map(fn (array $break) => [
        'after_period' => $break['after_period'],
        'minutes' => max(1, (int) round((strtotime($break['end']) - strtotime($break['start'])) / 60)),
        'name' => $break['name'] ?: 'Break',
        'short_name' => $break['short'] ?: 'BK',
    ])->values()->all();

    $trayTotal = collect($grid['tray'])->sum('unplaced');
    $placedTotal = count($grid['cards']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="kw-page-header mt-16 rounded-2xl bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] p-6 shadow-md">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 rounded-xl bg-white/10 p-3 text-[#D3D9D4]">
                        <x-icon name="table-cells" class="h-8 w-8" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-[#D3D9D4]">Timetabling</p>
                        <h2 class="mt-1 text-2xl font-semibold leading-tight text-white">Timetable</h2>
                        <p class="mt-1 text-sm text-white/95">
                            Drag lessons from the tray onto the grid. Clashing slots turn red and are refused.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <a href="{{ route('admin.timetable.settings.verification', $setting) }}"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-md bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/15">
                        Verification &amp; print
                    </a>
                    <a href="{{ route('admin.timetable.teacher-loads', ['academic_year_id' => $setting->academic_year_id, 'source' => $setting->is_published ? 'published' : 'working', 'setting' => $setting->id]) }}"
                        class="inline-flex min-h-11 items-center rounded-md bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/20 hover:bg-white/15">
                        Teacher loads
                    </a>
                    <a href="{{ route('admin.timetable.teaching-summary', ['academic_year_id' => $setting->academic_year_id, 'source' => $setting->is_published ? 'published' : 'working', 'setting' => $setting->id]) }}" class="inline-flex min-h-11 items-center rounded-md bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/20">Teaching summary</a>
                    <form method="GET" action="{{ route('admin.timetable.index') }}" class="flex items-center gap-2">
                        <label for="setting" class="sr-only">Timetable</label>
                        <select id="setting" name="setting" onchange="this.form.submit()"
                            class="min-h-11 rounded-md border-0 bg-white/10 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                            @foreach ($settings as $option)
                                <option value="{{ $option->id }}" @selected($option->id === $setting->id)
                                    class="text-slate-900">
                                    {{ $option->typeLabel() }} — {{ $option->name }}{{ $option->term_label ? ' — '.$option->term_label : '' }}{{ $option->is_published ? ' (published)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>

                    @if ($setting->is_published)
                        <span class="inline-flex min-h-11 items-center gap-1.5 rounded-md bg-emerald-500/20 px-3 py-2 text-xs font-semibold text-white ring-1 ring-emerald-200/40">
                            <x-icon name="badge-check" class="h-3.5 w-3.5" />
                            Published
                        </span>
                    @else
                        <form method="POST" action="{{ route('admin.timetable.settings.publish', $setting) }}"
                            onsubmit="return confirm('Publish this timetable to teachers, students and parents?')">
                            @csrf
                            <button type="submit"
                                class="inline-flex min-h-11 items-center gap-1.5 rounded-md bg-emerald-500/20 px-3 py-2 text-xs font-semibold text-white ring-1 ring-emerald-200/40 transition hover:bg-emerald-500/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                                <x-icon name="badge-check" class="h-3.5 w-3.5" />
                                Publish
                            </button>
                        </form>
                    @endif


                </div>
            </div>
        </div>
    </x-slot>

    <div class="px-4 pb-12 sm:px-6 lg:px-8">
        @if (session('success'))
            <div role="status"
                class="mb-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-100">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert"
                class="mb-4 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-700 dark:bg-rose-900/40 dark:text-rose-100">
                <p class="font-semibold">That timetable change could not be saved.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <details class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-brand-700 dark:bg-brand-800">
            <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-brand-100">Create another schedule</summary>
            <form method="POST" action="{{ route('admin.timetable.settings.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                @csrf
                <input name="name" required placeholder="Schedule name" class="min-h-11 rounded-md border-slate-300 text-sm dark:bg-brand-900">
                <select name="schedule_type" class="min-h-11 rounded-md border-slate-300 text-sm dark:bg-brand-900">
                    <option value="day">Day timetable</option>
                    <option value="afternoon">Afternoon / study timetable</option>
                </select>
                <label class="text-xs text-slate-600">Days per cycle
                    <input name="cycle_length" type="number" value="{{ $setting->schedule_type === 'afternoon' ? 5 : 6 }}" min="1" max="14" required class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm dark:bg-brand-900">
                </label>
                <label class="text-xs text-slate-600">Anchor date
                    <input name="cycle_anchor_date" type="date" value="{{ now()->toDateString() }}" required class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm dark:bg-brand-900">
                </label>
                <label class="text-xs text-slate-600">Day on anchor
                    <input name="cycle_anchor_day" type="number" value="1" min="1" max="14" required class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm dark:bg-brand-900">
                </label>
                <button class="min-h-11 self-end rounded-md bg-[#124E66] px-4 text-sm font-semibold text-white">Create schedule</button>
            </form>
        </details>

        {{-- ---------------------------------------------------------------------
             Day structure. One number — periods per day — decides the whole layout.
        ---------------------------------------------------------------------- --}}
        <section x-data="timetableDayStructure(@js($breakDefaults))"
            class="mb-4 rounded-xl border border-slate-200 bg-white shadow-sm transition-colors dark:border-brand-700 dark:bg-brand-800">
            <button type="button" x-on:click="open = !open"
                class="flex min-h-11 w-full items-center justify-between gap-3 px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66]">
                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800 dark:text-brand-100">
                    <x-icon name="clock" class="h-4 w-4 text-[#124E66] dark:text-brand-400" />
                    Day structure
                    <span class="font-normal text-slate-500 dark:text-brand-400">
                        {{ count($grid['periods']) }} periods a day &middot;
                        {{ $grid['setting']['cycle_length'] }} days a cycle &middot; {{ $grid['setting']['schedule_label'] }}
                        @if (count($grid['breaks']))
                            &middot; {{ count($grid['breaks']) }} {{ Str::plural('break', count($grid['breaks'])) }}
                        @endif
                    </span>
                </span>
                <span aria-hidden="true" class="text-slate-400 transition-transform dark:text-brand-400"
                    x-bind:class="open ? 'rotate-180' : ''">&#9662;</span>
            </button>

            <form method="POST" action="{{ route('admin.timetable.day-structure') }}" x-show="open" x-cloak
                class="border-t border-slate-200 px-4 py-4 dark:border-brand-700">
                @csrf
                <input type="hidden" name="setting_id" value="{{ $setting->id }}">

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="periods_per_day"
                            class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">
                            Periods per day
                        </label>
                        <input id="periods_per_day" name="periods_per_day" type="number" min="1" max="20" required
                            value="{{ old('periods_per_day', max(1, count($grid['periods']))) }}"
                            class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                    </div>
                    <div>
                        <label for="days_per_cycle"
                            class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">
                            Days per cycle
                        </label>
                        <input id="days_per_cycle" name="days_per_cycle" type="number" min="1" max="14" required
                            value="{{ old('days_per_cycle', $grid['setting']['cycle_length']) }}"
                            class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                    </div>
                    <div>
                        <label for="cycle_anchor_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">Cycle anchor date</label>
                        <input id="cycle_anchor_date" name="cycle_anchor_date" type="date" required value="{{ old('cycle_anchor_date', $grid['setting']['cycle_anchor_date'] ?? now()->toDateString()) }}" class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-brand-900">
                    </div>
                    <div>
                        <label for="cycle_anchor_day" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">Day on anchor date</label>
                        <input id="cycle_anchor_day" name="cycle_anchor_day" type="number" min="1" max="14" required value="{{ old('cycle_anchor_day', $grid['setting']['cycle_anchor_day'] ?? 1) }}" class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm dark:bg-brand-900">
                    </div>
                    <div>
                        <label for="first_period_start"
                            class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">
                            First period starts
                        </label>
                        <input id="first_period_start" name="first_period_start" type="time" required
                            value="{{ old('first_period_start', $firstPeriod['start'] ?? '07:30') }}"
                            class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                    </div>
                    <div>
                        <label for="period_minutes"
                            class="block text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">
                            Minutes per period
                        </label>
                        <input id="period_minutes" name="period_minutes" type="number" min="5" max="180" required
                            value="{{ old('period_minutes', $periodMinutes) }}"
                            class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                    </div>
                </div>

                <fieldset class="mt-4">
                    <legend class="text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-brand-300">
                        Breaks
                    </legend>

                    <template x-for="(entry, index) in breaks" x-bind:key="entry._key">
                        <div class="mt-2 flex flex-wrap items-end gap-2">
                            <label class="text-xs text-slate-600 dark:text-brand-300">
                                <span class="block">After period</span>
                                <input type="number" min="1" max="20" x-model="entry.after_period"
                                    x-bind:name="'breaks[' + index + '][after_period]'"
                                    class="mt-1 min-h-11 w-28 rounded-md border-slate-300 text-sm shadow-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                            </label>
                            <label class="text-xs text-slate-600 dark:text-brand-300">
                                <span class="block">Minutes</span>
                                <input type="number" min="1" max="180" x-model="entry.minutes"
                                    x-bind:name="'breaks[' + index + '][minutes]'"
                                    class="mt-1 min-h-11 w-28 rounded-md border-slate-300 text-sm shadow-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                            </label>
                            <label class="text-xs text-slate-600 dark:text-brand-300">
                                <span class="block">Name</span>
                                <input type="text" maxlength="60" x-model="entry.name"
                                    x-bind:name="'breaks[' + index + '][name]'"
                                    class="mt-1 min-h-11 w-40 rounded-md border-slate-300 text-sm shadow-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                            </label>
                            <label class="text-xs text-slate-600 dark:text-brand-300">
                                <span class="block">Short</span>
                                <input type="text" maxlength="20" x-model="entry.short_name"
                                    x-bind:name="'breaks[' + index + '][short_name]'"
                                    class="mt-1 min-h-11 w-24 rounded-md border-slate-300 text-sm shadow-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                            </label>
                            <button type="button" x-on:click="breaks.splice(index, 1)"
                                class="min-h-11 rounded-md px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:text-rose-300 dark:hover:bg-rose-900/40">
                                Remove
                            </button>
                        </div>
                    </template>

                    <button type="button" x-on:click="addBreak()" x-show="breaks.length < 6"
                        class="mt-3 inline-flex min-h-11 items-center gap-1.5 rounded-md border border-dashed border-slate-400 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-[#124E66] hover:text-[#124E66] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] dark:border-brand-600 dark:text-brand-200">
                        <x-icon name="plus" class="h-3.5 w-3.5" />
                        Add a break
                    </button>
                </fieldset>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-md bg-[#124E66] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#0d3c50] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2">
                        Apply day structure
                    </button>
                    <p class="text-xs text-slate-500 dark:text-brand-400">
                        Period times are regenerated from the start time and length. Shrinking the day is refused
                        while cards still sit in the periods being removed.
                    </p>
                </div>
            </form>
        </section>

        {{-- ---------------------------------------------------------------------
             The editor.
        ---------------------------------------------------------------------- --}}
        {{-- The payload goes through @js() because it is structured data; the endpoints are
             written plainly so the page source stays readable. --}}
        <div x-data="timetableGrid(@js($grid), {
            candidates: '{{ route('admin.timetable.grid.candidates') }}',
            preparation: '{{ route('admin.timetable.prepare', $setting) }}',
            move: '{{ route('admin.timetable.grid.move') }}',
            changeRoom: '{{ route('admin.timetable.grid.card-room') }}',
            cardAttendance: '{{ route('admin.timetable.grid.card-attendance') }}',
            unplace: '{{ route('admin.timetable.grid.unplace') }}',
            lock: '{{ route('admin.timetable.grid.lock') }}',
            storeLesson: '{{ route('admin.timetable.grid.lesson.store') }}',
            updateLesson: '{{ route('admin.timetable.grid.lesson.update') }}',
            duplicateLesson: '{{ route('admin.timetable.grid.lesson.duplicate') }}',
            destroyLesson: '{{ route('admin.timetable.grid.lesson.destroy') }}',
            requiredCount: '{{ route('admin.timetable.grid.required-count') }}',
            storeDivisions: '{{ route('admin.timetable.grid.divisions.store') }}',
            updateDivision: '{{ route('admin.timetable.grid.divisions.update', ['division' => '__DIVISION__']) }}',
            destroyDivision: '{{ route('admin.timetable.grid.divisions.destroy', ['division' => '__DIVISION__']) }}',
            storeRooms: '{{ route('admin.timetable.grid.rooms.store') }}',
            updateRoom: '{{ route('admin.timetable.grid.rooms.update', ['room' => '__ROOM__']) }}',
            destroyRoom: '{{ route('admin.timetable.grid.rooms.destroy', ['room' => '__ROOM__']) }}',
            updateBaseRooms: '{{ route('admin.timetable.grid.base-rooms.update') }}',
        })" x-cloak x-bind:style="{ '--tt-row': rowHeight }" x-on:click.window="closeMenu()"
            x-on:keydown.escape.window="closeOverlays()" x-on:scroll.window="syncHeading()" x-on:resize.window="syncHeading()"
            x-bind:class="trayOpen ? 'pb-80' : 'pb-24'" class="space-y-4 transition-[padding]">

            {{-- Classes, divisions and split groups ---------------------------- --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800">
                <button type="button" x-on:click="divisionsOpen = ! divisionsOpen"
                    class="flex min-h-12 w-full items-center justify-between gap-3 px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#124E66]">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800 dark:text-brand-100">Classes &amp; divisions</span>
                        <span class="block text-xs font-normal text-slate-500 dark:text-brand-400"
                            x-text="divisionCount + (divisionCount === 1 ? ' division' : ' divisions') + ' · define groups for split lessons'"></span>
                    </span>
                    <span aria-hidden="true" class="text-slate-400 transition-transform"
                        x-bind:class="divisionsOpen ? 'rotate-180' : ''">&#9662;</span>
                </button>

                <div x-show="divisionsOpen" x-transition class="border-t border-slate-200 p-4 dark:border-brand-700">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <label class="w-full sm:w-72">
                            <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Class</span>
                            <select x-model.number="divisionClassId" x-on:change="ensureNewDivisionClass()"
                                class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                <template x-for="klass in grid.classes" x-bind:key="klass.id">
                                    <option x-bind:value="klass.id" x-text="klass.name"></option>
                                </template>
                            </select>
                        </label>
                        <p class="max-w-xl text-xs text-slate-500 dark:text-brand-400">
                            A division contains mutually exclusive groups and may apply to several classes. Matching groups can attend one joint lesson.
                        </p>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <template x-for="division in selectedDivisionDrafts" x-bind:key="division.id">
                            <article class="rounded-xl border border-slate-200 p-3 dark:border-brand-700">
                                <label>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Division name</span>
                                    <input type="text" x-model="division.name" maxlength="100"
                                        class="mt-1 min-h-10 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                </label>
                                <fieldset class="mt-3">
                                    <legend class="text-xs font-semibold text-slate-700 dark:text-brand-200">Applies to classes</legend>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <template x-for="klass in grid.classes" x-bind:key="'division-' + division.id + '-class-' + klass.id">
                                            <label class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-md border border-slate-300 bg-slate-50 px-3 text-xs text-slate-700 dark:border-brand-600 dark:bg-brand-900 dark:text-brand-200">
                                                <input type="checkbox" x-bind:value="klass.id" x-model.number="division.class_ids"
                                                    class="rounded border-slate-300 text-[#124E66] focus:ring-[#124E66]">
                                                <span x-text="klass.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </fieldset>
                                <div class="mt-3 space-y-2">
                                    <template x-for="(group, groupIndex) in division.groups" x-bind:key="group.id || 'new-' + groupIndex">
                                        <div class="flex items-center gap-2">
                                            <input type="text" x-model="group.name" maxlength="100"
                                                x-bind:aria-label="'Group ' + (groupIndex + 1)"
                                                class="min-h-10 min-w-0 flex-1 rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                            <button type="button" x-on:click="removeDivisionGroup(division, groupIndex)"
                                                x-bind:disabled="division.groups.length <= 2"
                                                aria-label="Remove group"
                                                class="min-h-10 rounded-md border border-rose-300 px-3 font-bold text-rose-700 disabled:cursor-not-allowed disabled:opacity-30 dark:border-rose-700 dark:text-rose-300">&times;</button>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-3 flex flex-wrap justify-between gap-2">
                                    <button type="button" x-on:click="addDivisionGroup(division)"
                                        class="min-h-9 rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 dark:border-brand-600 dark:text-brand-200">+ Group</button>
                                    <span class="flex gap-2">
                                        <button type="button" x-on:click="deleteDivision(division)"
                                            class="min-h-9 rounded-md border border-rose-300 px-3 text-xs font-semibold text-rose-700 dark:border-rose-700 dark:text-rose-300">Delete</button>
                                        <button type="button" x-on:click="updateDivision(division)" x-bind:disabled="busy"
                                            class="min-h-9 rounded-md bg-[#124E66] px-3 text-xs font-semibold text-white disabled:opacity-50">Save division</button>
                                    </span>
                                </div>
                            </article>
                        </template>

                        <article class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/70 p-3 dark:border-brand-600 dark:bg-brand-900/40">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-brand-100">Add a division</h4>
                            <label class="mt-2 block">
                                <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Division name</span>
                                <input type="text" x-model="newDivision.name" maxlength="100" placeholder="Languages, Boys / Girls, Options…"
                                    class="tt-division-field mt-1 min-h-10 w-full rounded-md text-sm dark:text-brand-100">
                            </label>
                            <fieldset class="mt-3">
                                <legend class="text-xs font-semibold text-slate-700 dark:text-brand-200">Apply the same division to</legend>
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <template x-for="klass in grid.classes" x-bind:key="'new-division-class-' + klass.id">
                                        <label class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-xs text-slate-700 dark:border-brand-600 dark:bg-brand-900 dark:text-brand-200">
                                            <input type="checkbox" x-bind:value="klass.id" x-model.number="newDivision.class_ids"
                                                class="rounded border-slate-300 text-[#124E66] focus:ring-[#124E66]">
                                            <span x-text="klass.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </fieldset>
                            <div class="mt-3 space-y-2">
                                <template x-for="(group, groupIndex) in newDivision.groups" x-bind:key="groupIndex">
                                    <div class="flex items-center gap-2">
                                        <input type="text" x-model="newDivision.groups[groupIndex]" maxlength="100"
                                            x-bind:placeholder="'Group ' + (groupIndex + 1)"
                                            x-bind:aria-label="'New group ' + (groupIndex + 1)"
                                            class="tt-division-field min-h-10 min-w-0 flex-1 rounded-md text-sm dark:text-brand-100">
                                        <button type="button" x-on:click="removeDivisionGroup(newDivision, groupIndex)"
                                            x-bind:disabled="newDivision.groups.length <= 2" aria-label="Remove new group"
                                            class="min-h-10 rounded-md border border-rose-300 px-3 font-bold text-rose-700 disabled:cursor-not-allowed disabled:opacity-30 dark:border-rose-700 dark:text-rose-300">&times;</button>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-3 flex flex-wrap justify-between gap-2">
                                <button type="button" x-on:click="addNewDivisionGroup()"
                                    class="min-h-9 rounded-md border border-slate-300 px-3 text-xs font-semibold text-slate-700 dark:border-brand-600 dark:text-brand-200">+ Group</button>
                                <button type="button" x-on:click="createDivision()" x-bind:disabled="busy"
                                    class="min-h-9 rounded-md bg-[#124E66] px-3 text-xs font-semibold text-white disabled:opacity-50">Add division</button>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            {{-- Rooms and baserooms -------------------------------------------- --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-brand-700 dark:bg-brand-800">
                <button type="button" x-on:click="roomsOpen = ! roomsOpen"
                    class="flex min-h-12 w-full items-center justify-between gap-3 px-4 py-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#124E66]">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800 dark:text-brand-100">Rooms &amp; baserooms</span>
                        <span class="block text-xs font-normal text-slate-500 dark:text-brand-400"
                            x-text="grid.editor.rooms.length + ' rooms · ' + assignedBaseRoomCount + ' classes assigned'"></span>
                    </span>
                    <span aria-hidden="true" class="text-slate-400 transition-transform"
                        x-bind:class="roomsOpen ? 'rotate-180' : ''">&#9662;</span>
                </button>

                <div x-show="roomsOpen" x-transition class="border-t border-slate-200 p-4 dark:border-brand-700">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-brand-100">Add rooms</h4>
                            <p class="mt-1 text-xs text-slate-500 dark:text-brand-400">
                                Enter the room details, then add it to this timetable revision.
                            </p>
                            <div class="mt-3 grid gap-2 sm:grid-cols-[minmax(0,1fr)_7rem_7rem]">
                                <label>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Name</span>
                                    <input type="text" x-model="newRoom.name" x-on:keydown.enter.prevent="addRooms()"
                                        placeholder="Science Lab" autocomplete="off"
                                        class="tt-room-field mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                </label>
                                <label>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Short name</span>
                                    <input type="text" x-model="newRoom.short_name" x-on:keydown.enter.prevent="addRooms()"
                                        placeholder="LAB" maxlength="20" autocomplete="off"
                                        class="tt-room-field mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                </label>
                                <label>
                                    <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Capacity</span>
                                    <input type="number" min="1" max="10000" x-model="newRoom.capacity"
                                        x-on:keydown.enter.prevent="addRooms()" placeholder="30"
                                        class="tt-room-field mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                </label>
                            </div>
                            <div class="mt-2 flex justify-end">
                                <button type="button" x-on:click="addRooms()" x-bind:disabled="busy || ! newRoom.name.trim()"
                                    class="min-h-10 rounded-md bg-[#124E66] px-4 text-sm font-semibold text-white hover:bg-[#0f4054] disabled:opacity-50">Add room</button>
                            </div>

                            <h4 class="mt-5 text-sm font-semibold text-slate-800 dark:text-brand-100">Existing rooms</h4>
                            <div class="mt-2 max-h-80 space-y-2 overflow-y-auto pr-1">
                                <template x-for="room in roomDrafts" x-bind:key="room.id">
                                    <div class="grid grid-cols-[minmax(0,1fr)_5rem_5rem_auto] gap-1.5 rounded-lg border border-slate-200 p-2 dark:border-brand-700">
                                        <input type="text" x-model="room.name" aria-label="Room name"
                                            class="tt-room-field min-h-9 min-w-0 rounded border-slate-300 px-2 text-xs dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                        <input type="text" x-model="room.short_name" aria-label="Short name" placeholder="Short"
                                            class="tt-room-field min-h-9 min-w-0 rounded border-slate-300 px-2 text-xs dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                        <input type="number" min="1" x-model="room.capacity" aria-label="Capacity" placeholder="Seats"
                                            class="tt-room-field min-h-9 min-w-0 rounded border-slate-300 px-2 text-xs dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                        <span class="flex gap-1">
                                            <button type="button" x-on:click="updateRoom(room)" title="Save room"
                                                class="min-h-9 rounded border border-slate-300 px-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-brand-600 dark:text-brand-200 dark:hover:bg-brand-700">Save</button>
                                            <button type="button" x-on:click="deleteRoom(room)" title="Delete room"
                                                class="min-h-9 rounded border border-rose-300 px-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/30">&times;</button>
                                        </span>
                                    </div>
                                </template>
                                <p x-show="roomDrafts.length === 0" class="rounded-lg bg-slate-50 p-4 text-center text-xs text-slate-500 dark:bg-brand-900 dark:text-brand-400">No rooms have been added yet.</p>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-brand-100">Class baserooms</h4>
                            <p class="mt-1 text-xs text-slate-500 dark:text-brand-400">
                                A lesson for one class uses its baseroom automatically unless the lesson names a special room.
                            </p>
                            <div class="mt-2 max-h-[28rem] space-y-2 overflow-y-auto pr-1">
                                <template x-for="klass in grid.classes" x-bind:key="klass.id">
                                    <label class="grid grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)] items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-brand-700">
                                        <span class="text-xs font-semibold text-slate-700 dark:text-brand-200" x-text="klass.name"></span>
                                        <select x-on:change="baseRoomDrafts[klass.id] = $event.target.value" x-bind:aria-label="klass.name + ' baseroom'"
                                            class="min-h-10 min-w-0 rounded-md border-slate-300 text-xs focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                            <option value="" x-bind:selected="baseRoomDrafts[klass.id] === ''">No baseroom</option>
                                            <template x-for="room in grid.editor.rooms" x-bind:key="room.id">
                                                <option x-bind:value="String(room.id)"
                                                    x-bind:selected="String(room.id) === String(baseRoomDrafts[klass.id])"
                                                    x-text="room.name"></option>
                                            </template>
                                        </select>
                                    </label>
                                </template>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" x-on:click="saveBaseRooms()" x-bind:disabled="busy"
                                    class="min-h-10 rounded-md bg-[#124E66] px-4 text-sm font-semibold text-white hover:bg-[#0f4054] disabled:opacity-50">Save baserooms</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Grid ------------------------------------------------------------ --}}
            <section class="min-w-0">
                {{-- Selection / status bar --}}
                <div
                    class="mb-2 flex min-h-[3.25rem] flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm transition-colors dark:border-brand-700 dark:bg-brand-800">
                    <template x-if="selected">
                        <div class="flex flex-1 flex-wrap items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded text-[10px] font-bold"
                                x-bind:style="swatch(selected)" x-text="selected.subject_short"></span>
                            <span class="text-xs font-semibold text-slate-800 dark:text-brand-100"
                                x-text="describe(selected)"></span>

                            <span class="ml-auto flex flex-wrap items-center gap-1.5">
                                <template x-if="selected.card_id">
                                    <button type="button" x-on:click="toggleLock(selected)"
                                        class="inline-flex min-h-9 items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] dark:border-brand-600 dark:text-brand-200 dark:hover:bg-brand-700"
                                        x-text="selected.locked ? 'Unlock' : 'Lock'"></button>
                                </template>
                                <template x-if="selected.card_id">
                                    <button type="button" x-on:click="unplace(selected)"
                                        class="inline-flex min-h-9 items-center gap-1 rounded-md border border-rose-300 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/40">
                                        Return to tray
                                    </button>
                                </template>
                                <button type="button" x-on:click="clearSelection()"
                                    class="inline-flex min-h-9 items-center rounded-md px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] dark:text-brand-400 dark:hover:text-brand-100">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="! selected">
                        <p class="flex-1 text-xs text-slate-500 dark:text-brand-400">
                            Pick a card up to shade the grid — green slots accept it, red ones name the clash.
                        </p>
                    </template>

                    <span class="text-[11px] font-medium text-slate-500 dark:text-brand-400">
                        {{ count($grid['classes']) }} classes · {{ count($grid['days']) * count($grid['periods']) }} slots
                    </span>
                </div>

                {{-- Refusal / success toast --}}
                <div x-show="message" x-transition x-cloak role="alert" x-bind:class="message && message.kind === 'error'
                    ? 'border-rose-300 bg-rose-50 text-rose-900 dark:border-rose-700 dark:bg-rose-900/40 dark:text-rose-100'
                    : 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-100'"
                    class="mb-2 flex items-start gap-2 rounded-lg border px-3 py-2 text-xs">
                    <span class="flex-1" x-text="message ? message.text : ''"></span>
                    <button type="button" x-on:click="message = null"
                        class="shrink-0 font-bold focus-visible:outline-none">&times;</button>
                </div>

                @if (count($grid['classes']) === 0)
                    <div
                        class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-brand-600 dark:bg-brand-800">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-brand-100">No classes yet</h3>
                        <p class="mx-auto mt-2 max-w-md text-xs text-slate-500 dark:text-brand-400">
                            Create the academic year's classes first. Every existing class automatically receives a row here.
                        </p>
                    </div>
                @elseif (count($grid['periods']) === 0)
                    <div
                        class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-brand-600 dark:bg-brand-800">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-brand-100">Set the day structure</h3>
                        <p class="mx-auto mt-2 max-w-md text-xs text-slate-500 dark:text-brand-400">
                            The class rows are ready. Open <strong>Day structure</strong> above to add the periods that form the grid.
                        </p>
                    </div>
                @else
                    <div data-timetable-grid class="w-full overflow-visible rounded-xl border-2 border-slate-500 bg-white shadow-sm transition-colors dark:border-brand-400 dark:bg-brand-800" x-ref="timetable">
                        <div class="w-full min-w-0 align-top">
                            {{-- Header: days, then periods --}}
                            {{-- Reserve the header's height while it follows the viewport. --}}
                            <div x-ref="headingSpace" x-bind:style="{ height: headingHeight ? headingHeight + 'px' : null }">
                            <div x-ref="heading" aria-label="Timetable days and periods" x-bind:style="headingStyle"
                                class="relative z-40 rounded-t-[0.625rem] bg-white shadow-md ring-1 ring-inset ring-slate-300 dark:bg-brand-800 dark:ring-brand-500">
                                <div class="flex border-b-2 border-slate-500 dark:border-brand-400">
                                    <div
                                        class="z-10 w-20 shrink-0 border-r-2 border-slate-500 bg-white px-1 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 sm:w-24 dark:border-brand-400 dark:bg-brand-800 dark:text-brand-400">
                                        Class
                                    </div>
                                    <div class="grid min-w-0 flex-1" x-bind:style="{ gridTemplateColumns: columnTemplate }">
                                        <template x-for="(day, index) in grid.days" x-bind:key="day.number">
                                            <div x-bind:style="{ ...dayColour(day.number), gridColumn: (index * grid.periods.length + 1) + ' / span ' + grid.periods.length }"
                                                class="tt-day-heading tt-day-divider truncate px-0.5 py-0.5 text-center text-[9px] font-bold uppercase tracking-wide text-slate-600 sm:text-[10px] dark:text-brand-300"
                                                x-text="day.label"></div>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex border-b-2 border-slate-500 dark:border-brand-400">
                                    <div
                                        class="z-10 w-20 shrink-0 border-r-2 border-slate-500 bg-white sm:w-24 dark:border-brand-400 dark:bg-brand-800">
                                    </div>
                                    <div class="grid min-w-0 flex-1" x-bind:style="{ gridTemplateColumns: columnTemplate }">
                                        <template x-for="slot in slotList" x-bind:key="slot.key">
                                            <div x-bind:class="[slot.edgeClass, periodClass(slot), { 'tt-day-period': !drag && !selected }]" x-bind:style="dayColour(slot.day)"
                                                x-bind:aria-label="periodTitle(slot)"
                                                class="min-w-0 overflow-hidden py-1 text-center text-[8px] font-bold sm:text-[9px]"
                                                x-text="slot.short"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            </div>

                            {{-- One row per class --}}
                            <template x-for="klass in grid.classes" x-bind:key="klass.id">
                                <div class="tt-class-row flex">
                                    <div
                                        x-bind:style="drag?.class_ids?.includes(klass.id) ? 'background:#22c55e;color:#052e16;box-shadow:inset 5px 0 #166534' : ''"
                                        x-bind:title="drag?.class_ids?.includes(klass.id) ? klass.name + ' — dragged lesson belongs to this class' : klass.name"
                                        class="z-20 flex w-20 shrink-0 flex-col justify-center items-start border-r-2 border-slate-500 bg-white px-1 py-0.5 text-[10px] font-bold text-slate-700 sm:w-24 sm:text-xs dark:border-brand-400 dark:bg-brand-800 dark:text-brand-100"
                                        ><span x-text="klass.name"></span><span class="mt-1 text-[9px] font-medium opacity-70" x-text="capacityLabel(klass.id)"></span></div>

                                    <div class="relative grid min-w-0 flex-1" x-bind:style="{
                                        gridTemplateColumns: columnTemplate,
                                        gridTemplateRows: 'var(--tt-row)',
                                    }">
                                        {{-- Transparent interaction layer; never paint over placed cards. --}}
                                        <template x-for="slot in slotList" x-bind:key="slot.key">
                                            <div x-bind:style="{ gridColumn: String(slot.index + 1), gridRow: '1 / -1' }"
                                                x-bind:class="{ 'z-20': dragging }"
                                                x-bind:title="slotTitle(klass, slot)"
                                                x-on:dragover.prevent="onDragOver(klass, slot)"
                                                x-on:dragenter.prevent="hover = klass.id + ':' + slot.key"
                                                x-on:dragleave="clearHover(klass, slot)"
                                                x-on:drop.prevent="onDrop(klass, slot)"
                                                x-on:click="onSlotClick(klass, slot)"
                                                class="tt-drop-target">
                                            </div>
                                        </template>

                                        {{-- Paint all grid lines with the background, beneath cards in every state. --}}
                                        <div class="tt-grid-background pointer-events-none absolute inset-0 grid" x-bind:style="{ gridTemplateColumns: columnTemplate }" aria-hidden="true">
                                            <template x-for="slot in slotList" x-bind:key="slot.key">
                                                <div x-bind:class="[slotClass(klass, slot), slot.edgeClass, { 'tt-day-cell': !drag && !selected }]" x-bind:style="dayColour(slot.day)" class="transition-colors"></div>
                                            </template>
                                        </div>
                                        <template x-for="card in cardsFor(klass.id)" x-bind:key="card.key">
                                            <div draggable="true"
                                                x-bind:draggable="card.locked ? 'false' : 'true'"
                                                x-bind:style="cardStyle(card, klass.id)"
                                                x-bind:class="{
                                                    'pointer-events-none': dragging,
                                                    'ring-2 ring-offset-1 ring-[#124E66]': isSelected(gridCard(card)),
                                                    'cursor-grab active:cursor-grabbing': ! card.locked,
                                                    'cursor-not-allowed': card.locked,
                                                }"
                                                tabindex="0" role="button" x-bind:aria-label="cardTitle(card)"
                                                x-on:mouseenter="inspectCard(gridCard(card))"
                                                x-on:focus="inspectCard(gridCard(card))"
                                                x-on:keydown.enter.prevent="select(gridCard(card))"
                                                x-on:keydown.shift.f10.prevent="openMenu($event, gridCard(card))"
                                                x-on:dragstart="startDrag($event, gridCard(card))"
                                                x-on:dragend="endDrag()"
                                                x-on:click.stop="select(gridCard(card))"
                                                x-on:contextmenu.prevent.stop="openMenu($event, gridCard(card))"
                                                class="z-10 flex min-h-0 items-center justify-center overflow-hidden px-px text-[8px] font-bold leading-none ring-1 ring-inset ring-black/15 select-none sm:text-[9px]">
                                                <span class="truncate" x-text="card.subject_short"></span>
                                                <span x-show="missingRoom(card)" title="No room assigned"
                                                    class="ml-0.5 shrink-0 rounded bg-red-600 px-0.5 text-[8px] font-black text-white">R</span>
                                                <span x-show="missingTeacher(card)" title="No teacher assigned"
                                                    class="ml-0.5 shrink-0 rounded bg-red-600 px-0.5 text-[8px] font-black text-white">T</span>
                                                <span x-show="card.locked" aria-hidden="true"
                                                    class="ml-0.5 shrink-0 text-[8px]">&#128274;</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Legend --}}
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500 dark:text-brand-400">
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-3 w-3 rounded-sm bg-emerald-200 ring-1 ring-emerald-400"></span>
                            Part of a valid placement
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-3 w-3 rounded-sm bg-rose-200 ring-1 ring-rose-400"></span>
                            Clash — hover names it
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-3 w-3 rounded-sm bg-slate-200 ring-1 ring-slate-400 dark:bg-brand-700"></span>
                            Cannot fit here / another class
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span aria-hidden="true">&#128274;</span> Locked — unlock before moving
                        </span>
                        <span>For doubles, both periods turn green. Drop on the first period of the pair. Burnt orange divides days; grey divides periods 4 and 5.</span>
                    </div>

                    {{-- Cards the current day structure has no column for. Silence here would
                         read as "everything is placed" while cards sat off the edge. --}}
                    <div x-show="strayCards.length > 0" x-cloak role="alert"
                        class="mt-2 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-100">
                        <p>
                            <span x-text="strayCards.length"></span>
                            <span x-text="strayCards.length === 1 ? ' card sits' : ' cards sit'"></span>
                            outside the current day structure and cannot be drawn. Widen the day, or return the
                            affected cards to the tray here.
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="card in strayCards" x-bind:key="card.key">
                                <button type="button" x-on:click="select(gridCard(card))"
                                    class="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-amber-400 bg-white/70 px-2.5 py-1 font-semibold hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 dark:border-amber-600 dark:bg-brand-900/60 dark:hover:bg-brand-900">
                                    <span x-text="card.subject_short"></span>
                                    <span x-text="card.class_names"></span>
                                    <span aria-hidden="true">&rarr;</span>
                                    <span x-text="card.locked ? 'unlock first' : 'manage'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Tray ------------------------------------------------------------ --}}
            <section data-unplaced-tray
                aria-label="Unplaced lesson tray"
                x-on:dragover.prevent="onTrayDragOver($event)"
                x-on:dragleave="clearTrayHover($event)"
                x-on:drop.prevent="onTrayDrop()"
                x-bind:class="[
                    trayOpen ? 'max-h-[42vh]' : 'max-h-16',
                    trayHover
                        ? 'border-[#124E66] bg-cyan-50/95 ring-2 ring-[#124E66] dark:border-brand-400 dark:bg-brand-700/95 dark:ring-brand-400'
                        : 'border-slate-300 bg-white/95 dark:border-brand-600 dark:bg-brand-800/95'
                ]"
                class="fixed inset-x-3 bottom-3 z-40 mx-auto w-auto max-w-7xl overflow-hidden rounded-xl border p-3 shadow-2xl backdrop-blur-md transition-[max-height,background-color,border-color]">
                <div class="flex min-h-10 flex-wrap items-center justify-between gap-3">
                    <button type="button" x-show="countUndo" x-bind:disabled="busy" x-on:click="saveRequiredCount(true)" class="rounded border px-2 py-1 text-xs">Undo required-count change</button>
                    <button type="button" x-on:click="trayOpen = ! trayOpen"
                        x-bind:aria-expanded="trayOpen"
                        x-bind:aria-label="trayOpen ? 'Collapse unplaced lesson tray' : 'Expand unplaced lesson tray'"
                        class="group flex min-w-0 items-center gap-3 rounded-lg text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66]">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#124E66] text-sm font-bold text-white shadow-sm dark:bg-brand-500">
                            <span x-text="trayOpen ? '↓' : '↑'"></span>
                        </span>
                        <span class="min-w-0">
                            <span class="flex items-baseline gap-2">
                                <span class="text-sm font-semibold text-slate-800 dark:text-brand-100">Unplaced lessons</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-brand-900 dark:text-brand-200"
                                    x-text="unplacedTotal + ' left'">{{ $trayTotal }} left</span>
                            </span>
                            <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-brand-400">
                                <span x-text="grid.cards.length">{{ $placedTotal }}</span> cards placed.
                                <span x-show="trayOpen"> Repeated lesson cards are stacked; drag the top card to the grid, or a placed card back here.</span>
                                <span x-show="! trayOpen"> Click to show the lesson cards.</span>
                            </span>
                        </span>
                    </button>

                    <div x-show="trayOpen" x-transition.opacity class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto">
                        <label class="w-full sm:w-44">
                            <span class="sr-only">Show lessons for class</span>
                            <select id="tray-class-filter" x-model="classFilter"
                                class="min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                <option value="">Whole school</option>
                                <template x-for="klass in grid.classes" x-bind:key="'tray-filter-class-' + klass.id">
                                    <option x-bind:value="String(klass.id)" x-text="klass.name"></option>
                                </template>
                            </select>
                        </label>
                        <button type="button" x-on:click="openNewLesson()"
                            class="min-h-10 rounded-md bg-[#124E66] px-3 text-xs font-semibold text-white hover:bg-[#0f4054] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2">
                            + Create individual lesson
                        </button>
                          <button type="button" x-on:click="openPreparation()" x-bind:disabled="preparationLoading" class="inline-flex min-h-10 items-center rounded-md bg-[#124E66] px-3 text-xs font-semibold text-white" x-text="preparationLoading ? 'Loading assignments…' : 'Create cards from teacher assignments'"></button>
                          <label class="w-full sm:w-72">
                            <span class="sr-only">Filter lessons</span>
                            <input id="tray-filter" type="search" x-model="filter" placeholder="Filter class, subject or teacher"
                                class="min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                        </label>
                    </div>
                </div>

                <div x-show="trayOpen" x-transition.opacity class="mt-3 flex flex-col gap-3 sm:flex-row">
                <div class="flex min-w-0 flex-1 flex-wrap content-start gap-2 overflow-y-auto overscroll-contain p-1" style="max-height:24vh">
                    <template x-for="item in visibleTray" x-bind:key="item.stack_key || item.lesson_id">
                        <div data-card-stack class="relative shrink-0 self-start pt-2 pr-2" x-bind:style="{ width: item.span > 1 ? '104px' : '62px' }"
                            x-bind:aria-label="item.unplaced + ' stacked ' + item.subject + ' cards'">
                            <span x-show="item.unplaced > 2" aria-hidden="true"
                                class="absolute inset-x-2 top-0 bottom-2 rounded-lg border border-slate-400 bg-slate-200 shadow-sm dark:border-brand-400 dark:bg-brand-700"></span>
                            <span x-show="item.unplaced > 1" aria-hidden="true"
                                class="absolute inset-x-1 top-1 bottom-1 rounded-lg border border-slate-400 bg-slate-100 shadow-sm dark:border-brand-400 dark:bg-brand-800"></span>
                            <div draggable="true"
                                tabindex="0" role="button" x-bind:aria-label="describe(trayCard(item)) + ', ' + item.unplaced + ' cards left'"
                                x-on:mouseenter="inspectCard(trayCard(item))"
                                x-on:focus="inspectCard(trayCard(item))"
                                x-on:keydown.enter.prevent="select(trayCard(item))"
                                x-on:keydown.delete.prevent.stop="openRequiredCount(trayCard(item))"
                                x-on:keydown.shift.f10.prevent="openMenu($event, trayCard(item))"
                                x-on:dragstart="startDrag($event, trayCard(item))"
                                x-on:dragend="endDrag()"
                                x-on:click="select(trayCard(item))"
                                x-on:contextmenu.prevent.stop="openMenu($event, trayCard(item))"
                                x-bind:class="isSelected(trayCard(item)) ? 'ring-2 ring-[#124E66] dark:ring-brand-400' : 'ring-1 ring-slate-300 dark:ring-brand-600'"
                                x-bind:style="{ ...swatch(item), height: (64 * trayShare(item).size / trayShare(item).count) + 'px' }"
                                class="relative z-10 flex h-16 cursor-grab items-center justify-center rounded-sm border border-black/25 px-1 text-[11px] font-bold shadow-md transition hover:-translate-y-0.5 active:cursor-grabbing focus-visible:outline focus-visible:outline-2">
                                <span class="truncate" x-text="item.subject_short"></span>
                                <span class="absolute bottom-0.5 right-1 text-[9px] opacity-75" x-text="item.unplaced"></span>
                            </div>
                        </div>
                    </template>

                    <p x-show="visibleTray.length === 0"
                        class="rounded-lg bg-slate-50 px-3 py-6 text-center text-xs text-slate-500 sm:col-span-2 lg:col-span-3 xl:col-span-4 2xl:col-span-5 dark:bg-brand-900 dark:text-brand-400">
                        <span x-show="filter">Nothing matches that filter.</span>
                        <span x-show="! filter && classFilter">There are no unplaced lessons for the selected class.</span>
                        <span x-show="! filter && ! classFilter">Every lesson is placed.</span>
                    </p>
                </div>
                <aside data-card-info aria-label="Lesson information" class="shrink-0 overflow-y-auto rounded-lg bg-slate-800 p-3 text-slate-100 sm:w-72" style="max-height:24vh">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-300">Lesson information</p>
                    <template x-if="inspected">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="flex h-9 min-w-9 items-center justify-center rounded-sm px-1 text-xs font-bold" x-bind:style="swatch(inspected)" x-text="inspected.subject_short"></span>
                                <p class="text-sm font-bold" x-text="inspected.subject"></p>
                            </div>
                            <p class="mt-2 text-sm" x-text="inspected.class_names + ' · ' + groupLabel(inspected)"></p>
                            <p class="mt-1 text-xs" x-text="inspected.teachers?.join(', ') || 'No teacher assigned'"></p>
                            <p class="mt-1 text-xs" x-text="inspected.card_id ? (inspected.room || 'No room assigned') : trayRoomLine(inspected)"></p>
                            <p class="mt-1 text-xs text-slate-300" x-text="(inspected.day ? dayLabel(inspected.day) + ' · P' + inspected.period + ' · ' : 'Unplaced · ') + inspected.span + (inspected.span === 1 ? ' period' : ' periods')"></p>
                            <p x-show="inspected.split_key" class="mt-1 text-xs">Split lesson · moves together</p>
                            <p x-show="inspected.locked" class="mt-1 text-xs">Locked</p>
                            <button type="button" x-bind:disabled="busy" x-on:click="openRequiredCount(inspected)" class="mt-2 rounded border border-slate-400 px-2 py-1 text-xs disabled:opacity-50">Change required count…</button>
                        </div>
                    </template>
                    <p x-show="! inspected" class="text-xs text-slate-300">Hover over or focus a card to see its subject, class, teacher and room here.</p>
                </aside>
                </div>
            </section>

            {{-- Right-click actions ------------------------------------------------ --}}
            <div x-show="context.open" x-transition.opacity x-cloak x-on:click.stop
                x-bind:style="{ left: context.x + 'px', top: context.y + 'px', zIndex: 100 }"
                role="menu" aria-label="Lesson actions"
                class="fixed z-50 w-56 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-2xl dark:border-brand-600 dark:bg-brand-800">
                <div class="border-b border-slate-100 px-3 py-2 dark:border-brand-700">
                    <p class="truncate text-xs font-bold text-slate-800 dark:text-brand-100" x-text="context.subject?.subject"></p>
                    <p class="truncate text-[11px] text-slate-500 dark:text-brand-400" x-text="context.subject?.class_names"></p>
                </div>
                <button type="button" role="menuitem" x-on:click="openEditor(context.subject)"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Edit lesson…</button>
                <button type="button" role="menuitem" x-on:click="openEditor(context.subject, 'teachers')"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Change teachers…</button>
                <button type="button" role="menuitem" aria-haspopup="menu" x-bind:aria-expanded="context.roomsOpen || false"
                    x-on:mouseenter="context.subject?.card_id && openRoomMenu($event)"
                    x-on:click="context.subject?.card_id ? openRoomMenu($event) : openEditor(context.subject, 'rooms')"
                    x-on:keydown.arrow-right.prevent="openRoomMenu($event)"
                    class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-amber-100 dark:text-brand-100 dark:hover:bg-brand-700"><span>Change room</span><span aria-hidden="true">▸</span></button>
                <button type="button" role="menuitem" x-on:click="openEditor(context.subject, 'attendance')"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Attendance — all cards…</button>
                <button type="button" role="menuitem" x-on:click="openEditor(context.subject, 'cardAttendance')" class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Attendance — this card only…</button>
                <div class="my-1 border-t border-slate-100 dark:border-brand-700"></div>
                <button type="button" role="menuitem" x-on:click="openEditor(context.subject, 'duration', 1)"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Make single</button>
                <button type="button" role="menuitem" x-on:click="openEditor(context.subject, 'duration', 2)"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Make double</button>
                <template x-if="context.subject?.card_id">
                    <button type="button" role="menuitem" x-on:click="toggleLock(context.subject); closeMenu()"
                        class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700"
                        x-text="context.subject?.locked ? 'Unlock card' : 'Lock card'"></button>
                </template>
                <template x-if="context.subject?.card_id">
                    <button type="button" role="menuitem" x-on:click="unplace(context.subject); closeMenu()"
                        class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Return card to tray</button>
                </template>
                <div class="my-1 border-t border-slate-100 dark:border-brand-700"></div>
                <button type="button" role="menuitem" x-on:click="duplicateLesson(context.subject)"
                    class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:text-brand-100 dark:hover:bg-brand-700">Duplicate lesson</button>
                <button type="button" role="menuitem" x-bind:disabled="busy" x-on:click="openRequiredCount(context.subject)" class="block w-full px-3 py-2 text-left hover:bg-slate-100 dark:hover:bg-brand-700">Change required count…</button>
                <button type="button" role="menuitem" x-show="context.subject?.card_id" x-on:click="deleteLesson(context.subject)"
                    class="block w-full px-3 py-2 text-left text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/30">Delete lesson…</button>
            </div>

            <div x-show="context.open && context.roomsOpen" x-cloak x-on:click.stop
                x-bind:style="{ left: context.roomX + 'px', top: context.roomY + 'px', width: 'min(288px, calc(100vw - 16px))', maxHeight: 'min(420px, calc(100vh - 16px))', zIndex: 101 }"
                x-on:keydown.arrow-left.prevent="context.roomsOpen = false" role="menu" aria-label="Change card room"
                class="fixed overflow-y-auto rounded-lg border border-slate-300 bg-white py-1 text-sm shadow-2xl dark:border-brand-600 dark:bg-brand-800 dark:text-brand-100">
                <p class="border-b border-slate-200 px-3 py-2 text-xs font-semibold dark:border-brand-600">Room for this card</p>
                <template x-for="room in grid.editor.rooms" x-bind:key="room.id">
                    <button type="button" role="menuitemradio" x-bind:aria-checked="context.subject?.room_id === room.id"
                        x-bind:disabled="busy || context.subject?.locked" x-on:click="changeCardRoom(room.id)"
                        x-bind:title="roomMenuStatus(room).label"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-sky-100 disabled:opacity-50 dark:hover:bg-brand-700">
                        <span class="w-4 shrink-0 text-center font-bold" x-bind:class="roomMenuStatus(room).occupied ? 'text-red-600' : 'text-blue-600'" x-text="roomMenuStatus(room).symbol"></span>
                        <span x-text="(room.short_name ? '(' + room.short_name + ') ' : '') + room.name"></span>
                        <span class="sr-only" x-text="roomMenuStatus(room).label"></span>
                    </button>
                </template>
                <p x-show="! grid.editor.rooms.length" class="px-3 py-2 text-xs">No rooms configured.</p>
                <button type="button" role="menuitemradio" x-bind:aria-checked="context.subject?.room_id == null" x-bind:disabled="busy || context.subject?.locked"
                    x-on:click="changeCardRoom(null)" class="mt-1 flex w-full gap-2 border-t border-slate-200 px-3 py-2 text-left hover:bg-sky-100 disabled:opacity-50 dark:border-brand-600 dark:hover:bg-brand-700">
                    <span class="w-4 text-blue-600" x-text="context.subject?.room_id == null ? '✓' : ''"></span>Empty the classroom
                </button>
                <p class="px-3 py-2 text-[11px] text-slate-500 dark:text-brand-300" x-text="context.subject?.locked ? 'Unlock this card to change its room.' : '✓ Current room · × Occupied at this time. Availability is checked when saved.'"></p>
            </div>

            <template x-if="countEditor">
                <div class="fixed inset-0 flex items-center justify-center bg-slate-950/60 p-4" style="z-index:115" role="dialog" aria-modal="true" aria-label="Change required lesson count">
                    <section class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl dark:bg-brand-800">
                        <h2 class="text-lg font-bold">Change required count</h2>
                        <p class="mt-1 text-sm" x-text="countEditor.subject + ' · ' + countEditor.class_names"></p>
                        <p class="mt-3 text-sm" x-text="countEditor.required + ' required · ' + countEditor.placed + ' placed · ' + countEditor.unplaced + ' remaining'"></p>
                        <p class="mt-1 text-xs text-slate-500" x-text="'Each card uses ' + countEditor.span + ' period(s). This changes timetable demand, not teacher–subject assignments.'"></p>
                        <label class="mt-4 block text-sm font-semibold">Required cards per cycle
                            <input type="number" x-model.number="countEditor.value" x-bind:min="countEditor.placed" max="280" step="1" class="mt-1 w-full rounded border-slate-300 dark:bg-brand-900">
                        </label>
                        <p class="mt-3 text-sm font-semibold" x-text="'Required periods: ' + (countEditor.required * countEditor.span) + ' → ' + (Number(countEditor.value) * countEditor.span) + '. Remaining cards: ' + Math.max(0, Number(countEditor.value) - countEditor.placed)"></p>
                        <p class="mt-2 text-xs">Placed cards stay on the grid. You can undo this count change after saving.</p>
                        <p x-show="message?.kind === 'error'" x-text="message?.text" class="mt-2 text-sm text-red-600" role="alert"></p>
                        <div class="mt-4 flex justify-end gap-2"><button type="button" x-on:click="countEditor = null" x-bind:disabled="busy" class="rounded border px-3 py-2">Cancel</button><button type="button" x-on:click="saveRequiredCount()" x-bind:disabled="busy || Number(countEditor.value) < countEditor.placed || Number(countEditor.value) === countEditor.required || !!countEditor.split_key" class="rounded bg-[#124E66] px-3 py-2 text-white disabled:opacity-40">Confirm required count</button></div>
                        <p x-show="countEditor.split_key" class="mt-2 text-xs">Adjust linked split counts together in the assignment editor.</p>
                    </section>
                </div>
            </template>

            <template x-if="preparationPayload">
                <div class="fixed inset-0 overflow-y-auto bg-slate-950/60 p-2 sm:p-5" style="z-index:110" role="dialog" aria-modal="true" aria-label="Lessons from teacher assignments"
                    x-on:timetable-prepared.stop="applyGrid($event.detail.grid); clearSelection()"
                    x-on:preparation-close.stop="preparationPayload = null; trayOpen = true">
                    <div class="mx-auto max-w-screen-2xl rounded-xl bg-white shadow-2xl dark:bg-brand-800">
                        <div class="px-4 pt-3 text-sm font-bold">{{ $setting->name }} · Teacher assignments</div>
                        @include('admin.timetable.partials.preparation-workspace', ['embedded' => true])
                    </div>
                </div>
            </template>

            {{-- Lesson editor ------------------------------------------------------ --}}
            <div x-show="editor.open" x-transition.opacity x-cloak
                style="z-index: 100" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-4"
                x-on:click.self="editor.mode === 'edit' && closeEditor()">
                <form x-on:submit.prevent="saveLesson()"
                    style="max-height: 92vh" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-2xl dark:bg-brand-800">
                    <div class="sticky top-0 z-10 flex items-start justify-between border-b border-slate-200 bg-white px-5 py-4 dark:border-brand-700 dark:bg-brand-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-brand-50"
                                x-text="editor.attendanceOnly ? 'Attendance — this card only' : (editor.mode === 'create' ? 'Create lesson cards' : 'Edit lesson')"></h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-brand-400"
                                x-text="editor.mode === 'create' ? 'Uses the school’s existing class, subject and teacher assignments.' : editor.subject?.class_names"></p>
                        </div>
                        <button type="button" x-show="editor.mode === 'edit'" x-on:click="closeEditor()" aria-label="Close"
                            class="rounded px-2 py-1 text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-brand-700">&times;</button>
                    </div>

                    <p x-show="editor.attendanceOnly" class="px-5 pt-4 text-sm text-slate-600 dark:text-brand-200">Only this occurrence changes. Other cards keep their attendance. For a tray stack, one card is separated from the stack.</p>
                    <div class="grid gap-5 p-5 sm:grid-cols-2" :class="{ 'tt-attendance-only': editor.attendanceOnly }">
                        <fieldset class="sm:col-span-2" x-ref="attendanceEditor">
                            <legend class="sr-only">Attending classes and groups</legend>
                            <div class="flex flex-wrap items-end justify-between gap-2">
                                <span>
                                    <span class="block text-xs font-semibold text-slate-700 dark:text-brand-200">Attending classes and groups</span>
                                    <span class="mt-0.5 block text-[11px] text-slate-500 dark:text-brand-400">
                                        Choose the entire class or one or more groups from the same division. The same subject may have separate parallel group lessons.
                                    </span>
                                </span>
                                <button type="button" x-on:click="addAttendance()"
                                    x-bind:disabled="uniqueAttendanceClassIds().length >= grid.classes.length"
                                    class="min-h-9 rounded-md border border-[#124E66] px-3 text-xs font-semibold text-[#124E66] disabled:cursor-not-allowed disabled:opacity-40 dark:border-brand-400 dark:text-brand-200">
                                    + Joint class
                                </button>
                            </div>

                            <div class="mt-2 space-y-2">
                                <template x-for="(row, rowIndex) in editor.form.attendance" x-bind:key="rowIndex">
                                    <div class="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] dark:border-brand-700 dark:bg-brand-900/60">
                                        <label>
                                            <span class="sr-only">Class</span>
                                            <select x-model.number="row.class_id" x-on:change="defaultAttendanceGroup(row); syncLessonRecommendations()"
                                                class="min-h-10 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                                <template x-for="klass in grid.classes" x-bind:key="klass.id">
                                                    <option x-bind:value="klass.id"
                                                        x-bind:selected="Number(row.class_id) === klass.id"
                                                        x-bind:disabled="classAlreadySelected(klass.id, rowIndex)"
                                                        x-text="klass.name"></option>
                                                </template>
                                            </select>
                                        </label>
                                        <label>
                                            <span class="sr-only">Class group</span>
                                            <select x-model="row.group_id" x-on:change="syncLessonRecommendations()"
                                                class="min-h-10 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                                <option value="" x-bind:disabled="classHasAnotherAttendance(row.class_id, rowIndex)"
                                                    x-bind:selected="row.group_id === ''">Entire class</option>
                                                <template x-for="group in groupsForAttendance(row, rowIndex)" x-bind:key="group.id">
                                                    <option x-bind:value="String(group.id)"
                                                        x-bind:disabled="groupAlreadySelected(group.id, rowIndex)"
                                                        x-bind:selected="String(row.group_id) === String(group.id)"
                                                        x-text="group.division + ' — ' + group.name"></option>
                                                </template>
                                            </select>
                                        </label>
                                        <button type="button" x-on:click="removeAttendance(rowIndex)"
                                            x-bind:disabled="editor.form.attendance.length === 1"
                                            aria-label="Remove attending class"
                                            class="min-h-10 rounded-md border border-rose-300 px-3 text-sm font-bold text-rose-700 disabled:cursor-not-allowed disabled:opacity-30 dark:border-rose-700 dark:text-rose-300">&times;</button>
                                        <button type="button" x-show="matchingGroupRows(row).length > 0"
                                            x-on:click="addSharedAttendance(row)"
                                            class="text-left text-[11px] font-semibold text-[#124E66] sm:col-span-3 dark:text-brand-300">
                                            + Add matching group from linked classes
                                        </button>
                                        <button type="button" x-show="nextGroupForSameClass(row, rowIndex)"
                                            x-on:click="addGroupAttendance(row, rowIndex)"
                                            class="text-left text-[11px] font-semibold text-[#124E66] sm:col-span-3 dark:text-brand-300">
                                            + Add another group from this class
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" x-on:click="fixDivisions(editor.form.attendance[0]?.class_id)"
                                class="mt-2 text-left text-[11px] font-semibold text-[#124E66] underline-offset-2 hover:underline dark:text-brand-300">
                                Division missing or unclear? Fix it in Classes &amp; divisions.
                            </button>
                        </fieldset>

                        <label class="block sm:col-span-2">
                            <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Subject</span>
                            <select x-model.number="editor.form.subject_id" x-on:change="syncLessonRecommendations()"
                                class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                <template x-for="subject in lessonSubjects()" x-bind:key="subject.id">
                                    <option x-bind:value="subject.id" x-text="subject.name + (subject.code ? ' (' + subject.code + ')' : '')"></option>
                                </template>
                            </select>
                        </label>

                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Cards to place</span>
                            <input type="number" min="1" max="280" step="1" x-model.number="editor.form.cards_per_cycle"
                                class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                            <span class="mt-1 block text-[11px] text-slate-500" x-text="(Number(editor.form.cards_per_cycle) || 0) + ' card(s), using ' + ((Number(editor.form.cards_per_cycle) || 0) * (Number(editor.form.periods_per_card) || 1)) + ' timetable period(s).' "></span>
                        </label>
                        <div class="sm:col-span-2 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-700 dark:bg-brand-900 dark:text-brand-200">
                            <template x-for="classId in uniqueAttendanceClassIds()" x-bind:key="classId">
                                <p><span x-text="grid.classes.find(klass => klass.id === classId)?.name"></span>: <strong x-text="projectedCapacity(classId) + ' / ' + grid.setting.capacity + ' periods'"></strong> after saving</p>
                            </template>
                            <p x-show="exceedsCapacity()" class="mt-1 font-semibold text-amber-700 dark:text-amber-300" role="status">More cards than grid space. You can save them now and remove extras later.</p>
                            <p class="mt-1 opacity-75">Parallel options share capacity. Doubles use two periods. Counts include cards in the tray.</p>
                        </div>
                        <label class="block" x-ref="durationEditor">
                            <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Length of each card</span>
                            <select x-model.number="editor.form.periods_per_card"
                                class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                <option value="1">Single period</option>
                                <option value="2">Double period</option>
                                <option value="3">Triple period</option>
                                <option value="4">Four periods</option>
                            </select>
                        </label>

                        <fieldset class="sm:col-span-2" x-ref="teachersEditor">
                            <legend class="text-xs font-semibold text-slate-700 dark:text-brand-200">Teachers</legend>
                            <div class="mt-2 grid max-h-40 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2 dark:border-brand-700">
                                <template x-for="teacher in lessonTeachers()" x-bind:key="teacher.id">
                                    <label class="flex min-h-9 items-center gap-2 rounded px-2 text-sm hover:bg-slate-50 dark:text-brand-100 dark:hover:bg-brand-700">
                                        <input type="checkbox" x-bind:value="teacher.id" x-model.number="editor.form.teacher_ids"
                                            class="rounded border-slate-300 text-[#124E66] focus:ring-[#124E66]">
                                        <span x-text="teacher.name"></span>
                                        <span x-show="isTeacherRecommended(teacher.id)"
                                            x-text="teacherAssignmentLabel(teacher.id)"
                                            class="ml-auto rounded bg-cyan-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-cyan-900 dark:bg-cyan-900/60 dark:text-cyan-100"></span>
                                    </label>
                                </template>
                            </div>
                        </fieldset>

                        <fieldset class="sm:col-span-2" x-ref="roomsEditor">
                            <legend class="text-xs font-semibold text-slate-700 dark:text-brand-200">Allowed rooms</legend>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-brand-400">Select all rooms this lesson may use. Leave empty if it does not need a fixed room.</p>
                            <div class="mt-2 grid max-h-36 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2 dark:border-brand-700">
                                <template x-for="room in grid.editor.rooms" x-bind:key="room.id">
                                    <label class="flex min-h-9 items-center gap-2 rounded px-2 text-sm hover:bg-slate-50 dark:text-brand-100 dark:hover:bg-brand-700">
                                        <input type="checkbox" x-bind:value="room.id" x-model.number="editor.form.room_ids"
                                            class="rounded border-slate-300 text-[#124E66] focus:ring-[#124E66]">
                                        <span x-text="roomAvailabilityLabel(room)"></span>
                                    </label>
                                </template>
                            </div>
                        </fieldset>

                        <label x-show="editor.subject?.card_id" x-ref="placementRoomEditor" class="block sm:col-span-2">
                            <span class="text-xs font-semibold text-slate-700 dark:text-brand-200">Room for this card</span>
                            <select x-model="editor.form.placement_room_choice" x-on:change="syncPlacementRoomChoice()"
                                class="mt-1 min-h-11 w-full rounded-md border-slate-300 text-sm focus:border-[#124E66] focus:ring-[#124E66] dark:border-brand-600 dark:bg-brand-900 dark:text-brand-100">
                                <option value="automatic" x-text="automaticRoomLabel()"></option>
                                <option value="none">No room — leave this class without a room</option>
                                <template x-for="room in grid.editor.rooms" x-bind:key="room.id">
                                    <option x-bind:value="'room:' + room.id"
                                        x-text="roomAvailabilityLabel(room)"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500 dark:text-brand-400">
                                This changes only this card. A room already used in the same period will be refused.
                            </p>
                        </label>
                    </div>

                    <div class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 dark:border-brand-700 dark:bg-brand-900">
                        <button type="button" x-on:click="closeEditor()"
                            class="min-h-10 rounded-md border border-slate-300 px-4 text-sm font-semibold text-slate-700 dark:border-brand-600 dark:text-brand-200">Cancel</button>
                        <button type="submit" x-bind:disabled="busy"
                            class="min-h-10 rounded-md bg-[#124E66] px-4 text-sm font-semibold text-white hover:bg-[#0f4054] disabled:opacity-50"
                            x-text="busy ? 'Saving…' : (editor.mode === 'create' ? 'Add cards to tray' : 'Save changes')"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            /**
             * The break-repeater on the day-structure form. Small enough to keep separate
             * from the grid itself.
             */
            window.timetableDayStructure = function (breaks) {
                let nextKey = 0;

                return {
                    open: {{ $errors->any() ? 'true' : 'false' }},
                    breaks: breaks.map((entry) => Object.assign({ _key: nextKey++ }, entry)),

                    addBreak() {
                        if (this.breaks.length >= 6) {
                            return;
                        }

                        const last = this.breaks[this.breaks.length - 1];

                        this.breaks.push({
                            _key: nextKey++,
                            // after_period is required, so guess a plausible one rather than
                            // leaving a blank field that only fails on submit.
                            after_period: last ? Number(last.after_period || 0) + 2 : 4,
                            minutes: 20,
                            name: 'Break',
                            short_name: 'BK',
                        });
                    },
                };
            };

            /**
             * The grid.
             *
             * Two ways to place a card, because native drag-and-drop is mouse-only:
             * drag it, or click it and then click a slot. Both funnel into moveTo().
             */
            window.timetableGrid = function (initial, endpoints) {
                // Teachers without an imported colour still keep one stable colour everywhere.
                // The palette order deliberately alternates hue families, so consecutive teacher
                // ids do not produce nearly identical shades (as a simple hue hash would).
                const teacherPalette = [
                    '#A9D6F5', '#F6B3B3', '#B9E6C5', '#D4B8F0', '#FFD08A', '#8EDDE3',
                    '#F3B6D2', '#D7E88B', '#AEB8F2', '#F0BD98', '#8FD1B9', '#D9A6DF',
                    '#F7A889', '#B7DA82', '#83C5F4', '#F7D86F', '#B4A5E5', '#76D7D0',
                    '#EFA0B6', '#C7DD9B', '#9DBBE8', '#E7B57E', '#91CDB2', '#CFA4EC',
                ];

                return {
                    grid: initial,
                    endpoints,

                    slotList: [],
                    slotIndex: {},
                    cardIndex: {},
                    strayCards: [],

                    filter: '',
                    classFilter: '',
                    drag: null,
                    dragging: false,
                    dragToken: 0,
                    selected: null,
                    inspected: null,
                    countEditor: null,
                    countUndo: null,
                    preparationPayload: null,
                    preparationLoading: false,
                    preparationClassId: '',
                    verdicts: {},
                    candidatesState: 'idle',
                    headingHeight: 0,
                    headingStyle: {},
                    hover: null,
                    trayHover: false,
                    trayOpen: true,
                    message: null,
                    busy: false,
                    context: { open: false, x: 0, y: 0, subject: null },
                    editor: {
                        open: false,
                        mode: 'edit',
                        subject: null,
                        form: {
                            subject_id: null,
                            teacher_ids: [],
                            room_ids: [],
                            attendance: [],
                            placement_room_id: '',
                            placement_room_mode: 'automatic',
                            placement_room_choice: 'automatic',
                            periods_per_week: 1,
                            periods_per_card: 1,
                            cards_per_cycle: 1,
                        },
                    },
                    roomsOpen: false,
                    divisionsOpen: false,
                    divisionClassId: initial.classes[0]?.id || '',
                    divisionDrafts: [],
                    newDivision: {
                        name: '',
                        groups: ['', ''],
                        class_ids: initial.classes[0]?.id ? [initial.classes[0].id] : [],
                    },
                    newRoom: { name: '', short_name: '', capacity: '' },
                    roomDrafts: [],
                    baseRoomDrafts: {},

                    init() {
                        this.reindex();
                        this.syncRoomDrafts();
                        this.syncDivisionDrafts();
                        this.$nextTick(() => {
                            this.syncHeading();
                            this.headingObserver = new ResizeObserver(() => this.syncHeading());
                            [this.$refs.heading, this.$refs.timetable, document.getElementById('navbar')]
                                .filter(Boolean).forEach((element) => this.headingObserver.observe(element));
                        });
                    },

                    destroy() {
                        this.headingObserver?.disconnect();
                    },

                    syncHeading() {
                        const { heading, headingSpace, timetable } = this.$refs;
                        if (! heading || ! timetable) return;
                        const space = headingSpace.getBoundingClientRect();
                        const bounds = timetable.getBoundingClientRect();
                        const top = 0;
                        this.headingHeight = heading.offsetHeight;
                        this.headingStyle = space.top <= top && bounds.bottom > 0
                            ? { position: 'fixed', top: Math.min(top, bounds.bottom - this.headingHeight) + 'px', left: space.left + 'px', width: space.width + 'px' }
                            : {};
                    },

                    periodClass(slot) {
                        if (! (this.drag || this.selected)) return 'text-slate-500 dark:text-brand-400';
                        const state = this.indicatorState(slot);
                        if (state === 'available') return 'bg-green-400 text-slate-950 dark:bg-green-400 dark:text-slate-950';
                        if (state === 'clash') return 'bg-rose-500 text-white dark:bg-rose-500 dark:text-white';
                        if (state === 'loading') return 'bg-amber-400 text-slate-950 dark:bg-amber-400 dark:text-slate-950';
                        return 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-200';
                    },

                    validStartsFor(slot) {
                        const subject = this.drag || this.selected;
                        if (! subject || subject.locked) return [];
                        return Object.values(this.verdicts).filter((entry) => entry.ok
                            && entry.day === slot.day && entry.period <= slot.period
                            && slot.period < entry.period + Number(subject.span || 1));
                    },

                    clashesAt(slot) {
                        return Object.values(this.verdicts).filter((entry) => entry.day === slot.day)
                            .flatMap((entry) => entry.conflicts || [])
                            .filter((conflict) => ['teacher', 'room', 'students'].includes(conflict.kind)
                                && conflict.period === slot.period);
                    },

                    indicatorState(slot) {
                        if ((this.drag || this.selected)?.locked) return 'unavailable';
                        if (this.validStartsFor(slot).length) return 'available';
                        if (this.candidatesState === 'loading') return 'loading';
                        return this.clashesAt(slot).length ? 'clash' : 'unavailable';
                    },

                    roomAvailabilityLabel(room) {
                        const subject = this.editor.subject;
                        if (! subject?.card_id) return room.name + ' — availability checked when placed';
                        const occupants = this.grid.cards.filter((card) =>
                            card.room_id === room.id && card.day === subject.day
                            && ! card.card_ids.includes(subject.card_id)
                            && card.period < subject.period + Number(this.editor.form.periods_per_card || subject.span)
                            && subject.period < card.period + card.span
                        );
                        const names = [...new Set(occupants.map((card) => card.class_names))];
                        return room.name + (names.length ? ' — occupied by ' + names.join(', ') : ' — available');
                    },

                    periodTitle(slot) {
                        const subject = this.drag || this.selected;
                        if (! subject) return slot.title + ' — select a lesson card to check availability';
                        if (subject.locked) return slot.title + ' — unlock this card before moving it';
                        const starts = this.validStartsFor(slot);
                        if (starts.length) {
                            const placements = starts.map((entry) => Number(subject.span) > 1
                                ? entry.period + '–' + (entry.period + Number(subject.span) - 1)
                                : String(entry.period));
                            return slot.title + ' — Available in periods ' + placements.join(' or ')
                                + '. Drop on the first period of the placement.';
                        }
                        const clashes = this.clashesAt(slot);
                        if (clashes.length) return slot.title + ' — ' + this.reasons(clashes);
                        const verdict = this.verdictFor(slot);
                        const detail = verdict
                            ? (verdict.ok ? 'Available' : this.reasons(verdict.conflicts))
                            : (this.candidatesState === 'error' ? 'Availability could not be checked. Try selecting the card again.' : 'Checking availability…');
                        return slot.title + ' — ' + subject.subject + ': ' + detail;
                    },

                    // ---------------------------------------------------------------
                    // Derived state, rebuilt only when the payload changes. The grid can
                    // be 48 columns wide across ten classes, and a getter here would be
                    // re-run on every hover.
                    // ---------------------------------------------------------------
                    reindex() {
                        const periods = this.grid.periods;
                        const slots = [];

                        this.grid.days.forEach((day, dayIndex) => {
                            periods.forEach((period, periodIndex) => {
                                const last = periodIndex === periods.length - 1;

                                slots.push({
                                    key: day.number + ':' + period.number,
                                    day: day.number,
                                    period: period.number,
                                    short: period.short,
                                    index: dayIndex * periods.length + periodIndex,
                                    title: day.label + ' · ' + (period.name || 'Period ' + period.number)
                                        + (period.start ? ' · ' + period.start + '–' + period.end : ''),
                                    // Distinguish day boundaries from the grey 4–5 divider.
                                    // Placement rules still enforce all configured breaks.
                                    edgeClass: last
                                        ? 'tt-day-divider'
                                        : (period.number === 4
                                            ? 'tt-midday-divider'
                                            : 'tt-period-divider'),
                                });
                            });
                        });

                        this.slotList = slots;
                        this.slotIndex = Object.fromEntries(slots.map((slot) => [slot.key, slot.index]));

                        const byClass = {};
                        const strays = [];

                        this.grid.cards.forEach((card) => {
                            // A card can sit on a period the current day structure no longer
                            // has — stale data from a previously longer day, say. Drawing it in column 1
                            // would be a quiet lie, so count it and say so instead.
                            if (this.slotIndex[card.day + ':' + card.period] === undefined) {
                                strays.push(card);

                                return;
                            }

                            card.class_ids.forEach((id) => {
                                (byClass[id] = byClass[id] || []).push(card);
                            });
                        });

                        this.cardIndex = byClass;
                        this.strayCards = strays;
                    },

                    get columnTemplate() {
                        return 'repeat(' + (this.grid.days.length * this.grid.periods.length) + ', minmax(0, 1fr))';
                    },

                    get totalLanes() {
                        return this.grid.classes.length;
                    },

                    get rowHeight() {
                        const available = Math.max(180, window.innerHeight - 330);
                        const pixels = Math.floor(available / Math.max(1, this.totalLanes));

                        // A fixed class row must still have enough vertical space for
                        // parallel option cards to divide it into readable strips.
                        return Math.max(48, Math.min(64, pixels)) + 'px';
                    },

                    get unplacedTotal() {
                        return this.grid.tray.reduce((sum, item) => sum + item.unplaced, 0);
                    },

                    get visibleTray() {
                        const needle = this.filter.trim().toLowerCase();
                        const classId = Number(this.classFilter);

                        return this.grid.tray.filter((item) => {
                            const matchesClass = ! this.classFilter || item.class_ids.includes(classId);
                            const matchesSearch = ! needle || (
                                item.subject + ' ' + item.subject_short + ' ' + item.class_names + ' '
                                + item.teachers.join(' ')
                            ).toLowerCase().includes(needle);

                            return matchesClass && matchesSearch;
                        });
                    },

                    get assignedBaseRoomCount() {
                        return Object.values(this.baseRoomDrafts).filter(Boolean).length;
                    },

                    baseRoomFor(subject) {
                        if (! subject || subject.class_ids.length !== 1) {
                            return null;
                        }

                        const klass = this.grid.classes.find((entry) => entry.id === subject.class_ids[0]);

                        return klass && klass.base_room_id
                            ? this.grid.editor.rooms.find((room) => room.id === klass.base_room_id) || null
                            : null;
                    },

                    automaticRoomLabel() {
                        const baseRoom = this.baseRoomFor(this.editor.subject);

                        if (baseRoom) {
                            return 'Automatic — class baseroom (' + baseRoom.name + ')';
                        }

                        const firstAllowed = this.grid.editor.rooms.find((room) =>
                            this.editor.form.room_ids.includes(room.id)
                        );

                        return firstAllowed ? 'Automatic — first free allowed room' : 'Automatic — no fixed room';
                    },

                    syncPlacementRoomChoice() {
                        const choice = this.editor.form.placement_room_choice;

                        if (choice === 'none') {
                            this.editor.form.placement_room_mode = 'none';
                            this.editor.form.placement_room_id = '';

                            return;
                        }

                        if (choice.startsWith('room:')) {
                            const roomId = Number(choice.slice(5));
                            this.editor.form.placement_room_mode = 'room';
                            this.editor.form.placement_room_id = String(roomId);

                            if (! this.editor.form.room_ids.includes(roomId)) {
                                this.editor.form.room_ids.push(roomId);
                            }

                            return;
                        }

                        this.editor.form.placement_room_mode = 'automatic';
                        this.editor.form.placement_room_id = '';
                    },

                    cardsFor(classId) {
                        return this.cardIndex[classId] || [];
                    },

                    // ---------------------------------------------------------------
                    // What is being moved. A tray item names its lesson; a grid card
                    // names one of its rows and the server recovers the rest of the unit.
                    // ---------------------------------------------------------------
                    trayCard(item) {
                        return {
                            lesson_id: item.lesson_id,
                            preparation_key: item.preparation_key,
                            split_key: item.split_key,
                            card_id: null,
                            subject_id: item.subject_id,
                            subject: item.subject,
                            subject_short: item.subject_short,
                            colour: item.colour,
                            colour_key: item.colour_key,
                            teachers: item.teachers,
                            teacher_ids: item.teacher_ids,
                            room_ids: item.room_ids,
                            class_ids: item.class_ids,
                            class_names: item.class_names,
                            groups: item.groups,
                            span: item.span,
                            room: item.room,
                            room_id: item.room_id,
                            periods_per_week: item.periods_per_week,
                            cards_per_cycle: item.cards_per_cycle,
                            locked: false,
                        };
                    },

                    gridCard(card) {
                        return {
                            lesson_id: card.lesson_id,
                            preparation_key: card.preparation_key,
                            split_key: card.split_key,
                            card_id: card.card_ids[0],
                            subject_id: card.subject_id,
                            key: card.key,
                            subject: card.subject,
                            subject_short: card.subject_short,
                            colour: card.colour,
                            colour_key: card.colour_key,
                            teachers: card.teachers,
                            teacher_ids: card.teacher_ids,
                            room_ids: card.room_ids,
                            class_ids: card.class_ids,
                            class_names: card.class_names,
                            groups: card.groups,
                            span: card.span,
                            room: card.room,
                            room_id: card.room_id,
                            periods_per_week: card.periods_per_week,
                            cards_per_cycle: card.cards_per_cycle,
                            day: card.day,
                            period: card.period,
                            locked: card.locked,
                        };
                    },

                    isSelected(subject) {
                        if (! this.selected) {
                            return false;
                        }

                        return this.selected.card_id === subject.card_id
                            && this.selected.lesson_id === subject.lesson_id;
                    },

                    select(subject) {
                        this.inspectCard(subject);
                        if (this.dragging) {
                            return;
                        }

                        this.selected = this.isSelected(subject) ? null : subject;
                        this.message = null;

                        if (this.selected) {
                            this.loadCandidates(this.selected);
                        } else {
                            this.verdicts = {};
                            ++this.dragToken;
                        }
                    },

                    clearSelection() {
                        this.selected = null;
                        this.verdicts = {};
                        ++this.dragToken;
                        this.hover = null;
                    },

                    // ---------------------------------------------------------------
                    // Right-click menu and lesson editor
                    // ---------------------------------------------------------------
                    openMenu(event, subject) {
                        const width = 224;
                        const height = 390;

                        this.context = {
                            open: true,
                            x: Math.max(8, Math.min(event.clientX || event.currentTarget.getBoundingClientRect().left, window.innerWidth - width - 8)),
                            y: Math.max(8, Math.min(event.clientY || event.currentTarget.getBoundingClientRect().bottom, window.innerHeight - height - 8)),
                            roomsOpen: false,
                            subject,
                        };
                        this.selected = subject;
                        this.inspectCard(subject);
                        this.message = null;
                    },

                    closeMenu() {
                        this.context.open = false;
                        this.context.roomsOpen = false;
                    },

                    inspectCard(subject) {
                        if (! this.dragging) this.inspected = subject;
                    },

                    async openPreparation(classId = null) {
                        if (this.preparationLoading) return;
                        this.closeMenu();
                        this.preparationLoading = true;
                        this.preparationClassId = String(classId || this.classFilter || this.selected?.class_ids?.[0] || '');
                        try {
                            const response = await fetch(this.endpoints.preparation, { headers: { Accept: 'application/json' } });
                            if (! response.ok) throw new Error('Could not load teaching assignments. Please try again.');
                            this.preparationPayload = await response.json();
                            this.$nextTick(() => this.$el.querySelector('.prep select')?.focus());
                        } catch (error) {
                            this.say('error', error.message);
                        } finally {
                            this.preparationLoading = false;
                        }
                    },

                    openRoomMenu(event) {
                        if (! this.context.subject?.card_id) return;
                        const rect = event.currentTarget.getBoundingClientRect();
                        const width = Math.min(288, window.innerWidth - 16);
                        this.context.roomX = Math.max(8, this.context.x + 224 + width <= window.innerWidth - 8
                            ? this.context.x + 224 : this.context.x - width);
                        this.context.roomY = Math.max(8, Math.min(rect.top, window.innerHeight - Math.min(420, window.innerHeight - 16) - 8));
                        this.context.roomsOpen = true;
                    },

                    roomMenuStatus(room) {
                        const subject = this.context.subject;
                        if (! subject) return { symbol: '', label: '', occupied: false };
                        if (subject.room_id === room.id) return { symbol: '✓', label: 'Current room', occupied: false };
                        const occupants = this.grid.cards.filter((card) => card.room_id === room.id
                            && card.day === subject.day && ! card.card_ids.includes(subject.card_id)
                            && card.period < subject.period + subject.span && subject.period < card.period + card.span);
                        return occupants.length
                            ? { symbol: '×', label: 'Occupied by ' + [...new Set(occupants.map(card => card.class_names))].join(', '), occupied: true }
                            : { symbol: '', label: 'Available at this time', occupied: false };
                    },

                    async changeCardRoom(roomId) {
                        const subject = this.context.subject;
                        if (this.busy || ! subject?.card_id || subject.locked) return;
                        const data = await this.post(this.endpoints.changeRoom, {
                            setting_id: this.grid.setting.id, card_id: subject.card_id, room_id: roomId,
                        });
                        if (! data) return;
                        this.applyGrid(data.grid);
                        this.clearSelection();
                        this.closeMenu();
                        this.say('success', roomId === null ? 'Room cleared for this card.' : 'Room changed for this card.');
                    },

                    closeOverlays() {
                        if (this.preparationPayload) return;
                        if (this.countEditor) { this.countEditor = null; return; }
                        if (this.editor.open) {
                            // Creation is deliberately persistent: Escape and outside
                            // clicks must not discard a partially completed card setup.
                            if (this.editor.mode === 'edit') {
                                this.closeEditor();
                            }
                        } else {
                            this.closeMenu();
                        }
                    },

                    openEditor(subject, focus = null, duration = null) {
                        if (! subject) {
                            return;
                        }

                        if (subject.preparation_key && focus !== 'cardAttendance') {
                            this.closeMenu();
                            this.openPreparation(subject.class_ids?.[0]);
                            return;
                        }

                        this.closeMenu();
                        this.editor.mode = 'edit';
                        this.editor.attendanceOnly = focus === 'cardAttendance';
                        const hasPlacedCard = subject.card_id !== null && subject.card_id !== undefined;
                        const placementRoomChoice = hasPlacedCard
                            ? (subject.room_id === null || subject.room_id === undefined ? 'none' : 'room:' + subject.room_id)
                            : 'automatic';

                        this.editor.subject = subject;
                        this.editor.form = {
                            subject_id: subject.subject_id,
                            teacher_ids: [...(subject.teacher_ids || [])],
                            room_ids: [...(subject.room_ids || [])],
                            attendance: this.attendanceFor(subject),
                            placement_room_id: subject.room_id === null || subject.room_id === undefined
                                ? '' : String(subject.room_id),
                            placement_room_mode: placementRoomChoice === 'none' ? 'none'
                                : (placementRoomChoice.startsWith('room:') ? 'room' : 'automatic'),
                            placement_room_choice: placementRoomChoice,
                            periods_per_week: Number(subject.periods_per_week || 1),
                            cards_per_cycle: Number(subject.cards_per_cycle || 1),
                            periods_per_card: duration || Number(subject.span || 1),
                        };
                        this.editor.open = true;

                        this.$nextTick(() => {
                            if (focus) {
                                const target = focus === 'teachers' ? this.$refs.teachersEditor
                                    : (focus === 'rooms' ? (hasPlacedCard ? this.$refs.placementRoomEditor : this.$refs.roomsEditor)
                                        : (focus === 'attendance' ? this.$refs.attendanceEditor : this.$refs.durationEditor));
                                target?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            }
                        });
                    },

                    openNewLesson() {
                        const preferredClassId = this.selected?.class_ids?.[0]
                            || Number(this.divisionClassId)
                            || this.grid.classes[0]?.id;

                        if (! preferredClassId || this.grid.editor.subjects.length === 0) {
                            this.say('error', 'Add at least one class and subject before creating lesson cards.');

                            return;
                        }

                        this.closeMenu();
                        this.editor.mode = 'create';
                        this.editor.attendanceOnly = false;
                        this.editor.subject = null;
                        this.editor.form = {
                            subject_id: null,
                            teacher_ids: [],
                            room_ids: [],
                            attendance: [{ class_id: Number(preferredClassId), group_id: '' }],
                            placement_room_id: '',
                            placement_room_mode: 'automatic',
                            placement_room_choice: 'automatic',
                            periods_per_week: 1,
                            cards_per_cycle: 1,
                            periods_per_card: 1,
                        };

                        const subjects = this.lessonSubjects();
                        this.editor.form.subject_id = subjects[0]?.id || this.grid.editor.subjects[0]?.id || null;
                        this.syncLessonRecommendations();
                        this.editor.open = true;
                    },

                    closeEditor() {
                        this.editor.open = false;
                        this.editor.subject = null;
                    },

                    // ---------------------------------------------------------------
                    // Class divisions and lesson attendance
                    // ---------------------------------------------------------------
                    syncDivisionDrafts() {
                        this.divisionDrafts = this.grid.classes.flatMap((klass) =>
                            (klass.divisions || []).map((division) => ({
                                id: division.id,
                                class_id: klass.id,
                                shared_key: division.shared_key,
                                class_ids: [...(division.class_ids || [klass.id])],
                                name: division.name,
                                groups: division.groups.map((group) => ({
                                    id: group.id,
                                    name: group.name,
                                    shared_key: group.shared_key,
                                })),
                            }))
                        );

                        if (! this.grid.classes.some((klass) => klass.id === Number(this.divisionClassId))) {
                            this.divisionClassId = this.grid.classes[0]?.id || '';
                        }

                        this.ensureNewDivisionClass();
                    },

                    get selectedDivisionClass() {
                        return this.grid.classes.find((klass) => klass.id === Number(this.divisionClassId)) || null;
                    },

                    get selectedDivisionDrafts() {
                        return this.divisionDrafts.filter((division) =>
                            division.class_id === Number(this.divisionClassId)
                        );
                    },

                    get divisionCount() {
                        return new Set(this.grid.classes.flatMap((klass) =>
                            (klass.divisions || []).map((division) => division.shared_key || 'division-' + division.id)
                        )).size;
                    },

                    groupsForClass(classId) {
                        const klass = this.grid.classes.find((entry) => entry.id === Number(classId));

                        return (klass?.divisions || []).flatMap((division) =>
                            division.groups.map((group) => ({
                                id: group.id,
                                name: group.name,
                                division: division.name,
                                division_id: division.id,
                                shared_key: group.shared_key,
                                division_class_ids: [...(division.class_ids || [klass.id])],
                            }))
                        );
                    },

                    lessonSubjects() {
                        if (this.editor.mode !== 'create') {
                            return this.grid.editor.subjects;
                        }

                        const configuredClasses = this.editor.form.attendance
                            .map((row) => this.grid.classes.find((klass) => klass.id === Number(row.class_id)))
                            .filter((klass) => (klass?.subject_ids || []).length > 0);

                        if (configuredClasses.length === 0) {
                            return this.grid.editor.subjects;
                        }

                        const common = this.grid.editor.subjects.filter((subject) =>
                            configuredClasses.every((klass) => klass.subject_ids.includes(subject.id))
                        );

                        return common.length > 0 ? common : this.grid.editor.subjects;
                    },

                    recommendedTeacherIds() {
                        const classIds = new Set(this.editor.form.attendance.map((row) => Number(row.class_id)));
                        const subjectId = Number(this.editor.form.subject_id);
                        const assignments = (this.grid.editor.teacher_assignments || []).filter((entry) =>
                            classIds.has(entry.class_id) && entry.subject_id === subjectId
                        );
                        const ids = [];

                        classIds.forEach((classId) => {
                            const forClass = assignments.filter((entry) => entry.class_id === classId);
                            const primary = forClass.filter((entry) => entry.is_primary);

                            (primary.length > 0 ? primary : forClass).forEach((entry) => ids.push(entry.teacher_id));
                        });

                        return [...new Set(ids)];
                    },

                    isTeacherRecommended(teacherId) {
                        return this.recommendedTeacherIds().includes(Number(teacherId));
                    },

                    teacherAssignmentLabel(teacherId) {
                        const classIds = new Set(this.editor.form.attendance.map((row) => Number(row.class_id)));
                        const studentCount = (this.grid.editor.teacher_assignments || [])
                            .filter((entry) => entry.teacher_id === Number(teacherId)
                                && entry.subject_id === Number(this.editor.form.subject_id)
                                && classIds.has(entry.class_id))
                            .reduce((total, entry) => total + Number(entry.student_count || 0), 0);

                        return studentCount > 0 ? 'Assigned · ' + studentCount + ' students' : 'Assigned';
                    },

                    lessonTeachers() {
                        const recommended = new Set(this.recommendedTeacherIds());

                        return [...this.grid.editor.teachers].sort((left, right) => {
                            const order = Number(recommended.has(right.id)) - Number(recommended.has(left.id));

                            return order || left.name.localeCompare(right.name);
                        });
                    },

                    syncLessonRecommendations() {
                        if (this.editor.mode !== 'create') {
                            return;
                        }

                        const subjects = this.lessonSubjects();

                        if (! subjects.some((subject) => subject.id === Number(this.editor.form.subject_id))) {
                            this.editor.form.subject_id = subjects[0]?.id || null;
                        }

                        const classIds = this.uniqueAttendanceClassIds();
                        const selectedGroupIds = this.editor.form.attendance
                            .map((row) => Number(row.group_id))
                            .filter(Boolean)
                            .sort((left, right) => left - right);
                        const candidates = (this.grid.editor.lesson_presets || []).filter((lesson) => {
                            const lessonClassIds = [...(lesson.class_ids || [])]
                                .map(Number)
                                .sort((left, right) => left - right);

                            return lesson.subject_id === Number(this.editor.form.subject_id)
                                && lessonClassIds.length === classIds.length
                                && lessonClassIds.every((id, index) => id === classIds[index]);
                        });
                        const preset = candidates.find((lesson) => {
                            const lessonGroupIds = (lesson.groups || [])
                                .filter((entry) => ! entry.entire_class)
                                .map((entry) => Number(entry.id))
                                .sort((left, right) => left - right);

                            return selectedGroupIds.length > 0
                                && lessonGroupIds.length === selectedGroupIds.length
                                && lessonGroupIds.every((id, index) => id === selectedGroupIds[index]);
                        }) || candidates[0];

                        if (preset) {
                            this.editor.form.teacher_ids = [...(preset.teacher_ids || [])];
                            this.editor.form.room_ids = [...(preset.room_ids || [])];
                            this.editor.form.periods_per_week = Number(preset.periods_per_week || 1);
                            this.editor.form.cards_per_cycle = Number(preset.cards_per_cycle || 1);
                            this.editor.form.periods_per_card = Number(preset.periods_per_card || 1);
                            return;
                        }

                        this.editor.form.teacher_ids = this.recommendedTeacherIds();
                        this.editor.form.room_ids = [];
                        this.editor.form.periods_per_week = 1;
                        this.editor.form.cards_per_cycle = 1;
                        this.editor.form.periods_per_card = 1;
                    },

                    fixDivisions(classId) {
                        this.closeEditor();
                        this.divisionClassId = Number(classId) || this.grid.classes[0]?.id || '';
                        this.ensureNewDivisionClass();
                        this.divisionsOpen = true;
                    },

                    ensureNewDivisionClass() {
                        const selected = Number(this.divisionClassId);

                        if (selected && ! this.newDivision.class_ids.includes(selected)) {
                            this.newDivision.class_ids.push(selected);
                        }
                    },

                    addAttendance() {
                        const used = new Set(this.uniqueAttendanceClassIds());
                        const klass = this.grid.classes.find((entry) => ! used.has(entry.id));

                        if (klass) {
                            const row = { class_id: klass.id, group_id: '' };
                            this.defaultAttendanceGroup(row);
                            this.editor.form.attendance.push(row);
                            this.syncLessonRecommendations();
                        }
                    },

                    defaultAttendanceGroup(row) {
                        const peers = this.editor.form.attendance.filter(entry => entry !== row);
                        const selected = peers.flatMap(entry => this.groupsForClass(entry.class_id)
                            .filter(group => String(group.id) === String(entry.group_id)));
                        const groups = this.groupsForClass(row.class_id);
                        const match = groups.find(group => selected.some(peer => peer.shared_key && peer.shared_key === group.shared_key))
                            || groups.find(group => selected.some(peer => peer.name === group.name && peer.division === group.division));
                        row.group_id = match ? String(match.id) : '';
                    },

                    matchingGroupRows(row) {
                        const selected = this.groupsForClass(row.class_id).find((group) =>
                            String(group.id) === String(row.group_id)
                        );

                        if (! selected?.shared_key) {
                            return [];
                        }

                        const used = new Set(this.editor.form.attendance.map((entry) => Number(entry.class_id)));

                        return this.grid.classes.flatMap((klass) => {
                            if (used.has(klass.id)) {
                                return [];
                            }

                            const match = this.groupsForClass(klass.id).find((group) =>
                                group.shared_key === selected.shared_key
                            );

                            return match ? [{ class_id: klass.id, group_id: String(match.id) }] : [];
                        });
                    },

                    addSharedAttendance(row) {
                        this.editor.form.attendance.push(...this.matchingGroupRows(row));
                        this.syncLessonRecommendations();
                    },

                    removeAttendance(index) {
                        if (this.editor.form.attendance.length > 1) {
                            this.editor.form.attendance.splice(index, 1);
                            this.syncLessonRecommendations();
                        }
                    },

                    classAlreadySelected(classId, rowIndex) {
                        return this.editor.form.attendance.some((row, index) => index !== rowIndex
                            && Number(row.class_id) === Number(classId)
                            && (row.group_id === '' || this.editor.form.attendance[rowIndex]?.group_id === ''));
                    },

                    uniqueAttendanceClassIds() {
                        return [...new Set(this.editor.form.attendance
                            .map((row) => Number(row.class_id))
                            .filter(Boolean))]
                            .sort((left, right) => left - right);
                    },

                    attendanceFor(subject) {
                        return (subject.class_ids || []).flatMap((classId) => {
                            const groups = (subject.groups || []).filter((entry) =>
                                entry.class_id === classId && ! entry.entire_class
                            );

                            return groups.length > 0
                                ? groups.map((group) => ({ class_id: classId, group_id: String(group.id) }))
                                : [{ class_id: classId, group_id: '' }];
                        });
                    },

                    classHasAnotherAttendance(classId, rowIndex) {
                        return this.editor.form.attendance.some((row, index) =>
                            index !== rowIndex && Number(row.class_id) === Number(classId)
                        );
                    },

                    groupAlreadySelected(groupId, rowIndex) {
                        return this.editor.form.attendance.some((row, index) =>
                            index !== rowIndex && String(row.group_id) === String(groupId)
                        );
                    },

                    groupsForAttendance(row, rowIndex) {
                        const groups = this.groupsForClass(row.class_id);
                        const peer = this.editor.form.attendance.find((entry, index) =>
                            index !== rowIndex && Number(entry.class_id) === Number(row.class_id) && entry.group_id !== ''
                        );
                        const selected = peer && groups.find((group) => String(group.id) === String(peer.group_id));

                        return selected ? groups.filter((group) => group.division_id === selected.division_id) : groups;
                    },

                    nextGroupForSameClass(row, rowIndex) {
                        if (row.group_id === '') {
                            return null;
                        }

                        const selected = this.groupsForClass(row.class_id).find((group) =>
                            String(group.id) === String(row.group_id)
                        );

                        return this.groupsForAttendance(row, rowIndex).find((group) =>
                            group.division_id === selected?.division_id && ! this.editor.form.attendance.some((entry) =>
                                String(entry.group_id) === String(group.id)
                            )
                        ) || null;
                    },

                    addGroupAttendance(row, rowIndex) {
                        const group = this.nextGroupForSameClass(row, rowIndex);

                        if (group) {
                            this.editor.form.attendance.splice(rowIndex + 1, 0, {
                                class_id: Number(row.class_id),
                                group_id: String(group.id),
                            });
                            this.syncLessonRecommendations();
                        }
                    },

                    addDivisionGroup(target) {
                        if (target.groups.length < 12) {
                            target.groups.push({ id: null, name: '' });
                        }
                    },

                    addNewDivisionGroup() {
                        if (this.newDivision.groups.length < 12) {
                            this.newDivision.groups.push('');
                        }
                    },

                    removeDivisionGroup(target, index) {
                        if (target.groups.length > 2) {
                            target.groups.splice(index, 1);
                        }
                    },

                    async createDivision() {
                        const data = await this.post(this.endpoints.storeDivisions, {
                            setting_id: this.grid.setting.id,
                            class_ids: this.newDivision.class_ids.map(Number),
                            name: this.newDivision.name.trim(),
                            groups: this.newDivision.groups.map((name) => name.trim()),
                        });

                        if (data) {
                            this.applyGrid(data.grid);
                            this.newDivision = {
                                name: '',
                                groups: ['', ''],
                                class_ids: [Number(this.divisionClassId)],
                            };
                            this.divisionsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    async updateDivision(division) {
                        const data = await this.post(this.divisionUrl(this.endpoints.updateDivision, division.id), {
                            setting_id: this.grid.setting.id,
                            class_ids: division.class_ids.map(Number),
                            name: division.name.trim(),
                            groups: division.groups.map((group) => ({
                                id: group.id || null,
                                name: group.name.trim(),
                            })),
                        }, 'PUT');

                        if (data) {
                            this.applyGrid(data.grid);
                            this.divisionsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    async deleteDivision(division) {
                        if (! window.confirm('Delete ' + division.name + ' from every linked class?')) {
                            return;
                        }

                        const data = await this.post(this.divisionUrl(this.endpoints.destroyDivision, division.id), {
                            setting_id: this.grid.setting.id,
                        }, 'DELETE');

                        if (data) {
                            this.applyGrid(data.grid);
                            this.divisionsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    divisionUrl(template, divisionId) {
                        return template.replace('__DIVISION__', String(divisionId));
                    },

                    // ---------------------------------------------------------------
                    // Rooms and class baserooms
                    // ---------------------------------------------------------------
                    syncRoomDrafts() {
                        this.roomDrafts = this.grid.editor.rooms.map((room) => ({
                            id: room.id,
                            name: room.name,
                            short_name: room.short_name || '',
                            capacity: room.capacity || '',
                        }));
                        this.baseRoomDrafts = Object.fromEntries(this.grid.classes.map((klass) => [
                            klass.id,
                            klass.base_room_id === null ? '' : String(klass.base_room_id),
                        ]));
                    },

                    async addRooms() {
                        const data = await this.post(this.endpoints.storeRooms, {
                            setting_id: this.grid.setting.id,
                            name: this.newRoom.name.trim(),
                            short_name: this.newRoom.short_name.trim() || null,
                            capacity: this.newRoom.capacity === '' ? null : Number(this.newRoom.capacity),
                        });

                        if (data) {
                            this.applyGrid(data.grid);
                            this.newRoom = { name: '', short_name: '', capacity: '' };
                            this.roomsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    async updateRoom(room) {
                        const data = await this.post(this.roomUrl(this.endpoints.updateRoom, room.id), {
                            setting_id: this.grid.setting.id,
                            name: room.name,
                            short_name: room.short_name || null,
                            capacity: room.capacity === '' ? null : Number(room.capacity),
                        }, 'PUT');

                        if (data) {
                            this.applyGrid(data.grid);
                            this.roomsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    async deleteRoom(room) {
                        if (! window.confirm('Delete ' + room.name + '? Only unused rooms can be deleted.')) {
                            return;
                        }

                        const data = await this.post(this.roomUrl(this.endpoints.destroyRoom, room.id), {
                            setting_id: this.grid.setting.id,
                        }, 'DELETE');

                        if (data) {
                            this.applyGrid(data.grid);
                            this.roomsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    async saveBaseRooms() {
                        const data = await this.post(this.endpoints.updateBaseRooms, {
                            setting_id: this.grid.setting.id,
                            base_rooms: this.baseRoomDrafts,
                        }, 'PUT');

                        if (data) {
                            this.applyGrid(data.grid);
                            this.roomsOpen = true;
                            this.say('success', data.message);
                        }
                    },

                    roomUrl(template, roomId) {
                        return template.replace('__ROOM__', String(roomId));
                    },

                    async saveLesson() {
                        if (this.editor.attendanceOnly) {
                            if (this.busy) return;
                            const result = await this.post(this.endpoints.cardAttendance, {
                                setting_id: this.grid.setting.id, lesson_id: this.editor.subject.lesson_id,
                                card_id: this.editor.subject.card_id || null, version: this.grid.setting.preparation_version,
                                attendance: this.editor.form.attendance.map(row => ({ class_id: Number(row.class_id), group_id: Number(row.group_id) || null })),
                            });
                            if (result) { this.applyGrid(result.grid); this.closeEditor(); this.clearSelection(); this.say('success', result.message); }
                            return;
                        }
                        const subject = this.editor.subject;

                        if (this.editor.mode === 'edit' && ! subject) {
                            return;
                        }

                        const payload = {
                            setting_id: this.grid.setting.id,
                            subject_id: Number(this.editor.form.subject_id),
                            teacher_ids: this.editor.form.teacher_ids.map(Number),
                            room_ids: this.editor.form.room_ids.map(Number),
                            attendance: this.editor.form.attendance.map((row) => ({
                                class_id: Number(row.class_id),
                                group_id: row.group_id === '' ? null : Number(row.group_id),
                            })),
                            periods_per_week: Number(this.editor.form.periods_per_week),
                            cards_per_cycle: Number(this.editor.form.cards_per_cycle),
                            periods_per_card: Number(this.editor.form.periods_per_card),
                        };

                        if (this.editor.mode === 'edit') {
                            Object.assign(payload, {
                                lesson_id: subject.lesson_id,
                                card_id: subject.card_id,
                                placement_room_id: this.editor.form.placement_room_id === ''
                                    ? null : Number(this.editor.form.placement_room_id),
                                placement_room_mode: this.editor.form.placement_room_mode,
                            });
                        }

                        const endpoint = this.editor.mode === 'create'
                            ? this.endpoints.storeLesson
                            : this.endpoints.updateLesson;
                        const data = await this.post(endpoint, payload);

                        if (! data) {
                            return;
                        }

                        this.applyGrid(data.grid);
                        this.closeEditor();
                        this.clearSelection();
                        this.say('success', data.message || (this.editor.mode === 'create'
                            ? 'Lesson cards added to the tray.'
                            : 'Lesson updated.'));
                    },

                    async duplicateLesson(subject) {
                        this.closeMenu();
                        const data = await this.post(this.endpoints.duplicateLesson, {
                            setting_id: this.grid.setting.id,
                            lesson_id: subject.lesson_id,
                        });

                        if (data) {
                            this.applyGrid(data.grid);
                            this.say('success', data.message);
                        }
                    },

                    openRequiredCount(subject) {
                        const stack = this.grid.requirements.find(item => item.lesson_ids.includes(subject.lesson_id));
                        if (!stack) return;
                        this.closeMenu();
                        this.message = null;
                        this.countEditor = { ...stack, value: stack.required, version: this.grid.setting.preparation_version };
                    },

                    async saveRequiredCount(undo = false) {
                        if (this.busy) return;
                        const change = undo ? this.countUndo : this.countEditor;
                        if (!change) return;
                        const value = Number(change.value);
                        if (!Number.isInteger(value) || value < change.placed || value > 280) {
                            this.say('error', 'Choose a whole number at least as large as the placed count.'); return;
                        }
                        const data = await this.post(this.endpoints.requiredCount, {
                            setting_id: this.grid.setting.id, lesson_id: change.lesson_id,
                            required: value, expected_required: change.required, expected_placed: change.placed, version: change.version,
                        }, 'PUT');
                        if (!data) return;
                        this.applyGrid(data.grid);
                        this.countUndo = undo ? null : { ...change, required: value, value: change.required, version: data.grid.setting.preparation_version };
                        this.countEditor = null;
                        this.clearSelection();
                        this.say('success', data.message);
                    },

                    async deleteLesson(subject) {
                        this.closeMenu();

                        if (! window.confirm('Delete ' + subject.subject + ' for ' + subject.class_names
                            + '? All of its placed cards will also be deleted.')) {
                            return;
                        }

                        const data = await this.post(this.endpoints.destroyLesson, {
                            setting_id: this.grid.setting.id,
                            lesson_id: subject.lesson_id,
                        }, 'DELETE');

                        if (data) {
                            this.applyGrid(data.grid);
                            this.clearSelection();
                            this.say('success', data.message);
                        }
                    },

                    // ---------------------------------------------------------------
                    // Drag
                    // ---------------------------------------------------------------
                    startDrag(event, subject) {
                        if (subject.locked) {
                            event.preventDefault();
                            this.say('error', 'That card is locked. Unlock it before moving it.');

                            return;
                        }

                        const session = this.dragSession = (this.dragSession || 0) + 1;
                        this.drag = subject;
                        this.$nextTick(() => this.syncHeading());
                        this.selected = subject;
                        this.message = null;

                        event.dataTransfer.effectAllowed = 'move';
                        // Firefox refuses to start a drag without payload, even unused.
                        event.dataTransfer.setData('text/plain', String(subject.lesson_id));

                        // Deferred: putting pointer-events:none on the source element inside
                        // its own dragstart handler cancels the drag in some browsers.
                        setTimeout(() => {
                            // A cancelled drag must not leave invisible targets over cards.
                            if (this.drag && this.dragSession === session) this.dragging = true;
                        }, 0);

                        this.loadCandidates(subject);
                    },

                    endDrag() {
                        this.dragSession = (this.dragSession || 0) + 1;
                        this.drag = null;
                        this.dragging = false;
                        this.hover = null;
                        this.trayHover = false;
                    },

                    onTrayDragOver(event) {
                        if (! this.drag || ! this.drag.card_id || this.drag.locked) {
                            this.trayHover = false;

                            return;
                        }

                        event.dataTransfer.dropEffect = 'move';
                        this.trayOpen = true;
                        this.trayHover = true;
                    },

                    clearTrayHover(event) {
                        if (! event.currentTarget.contains(event.relatedTarget)) {
                            this.trayHover = false;
                        }
                    },

                    onTrayDrop() {
                        const subject = this.drag;

                        this.endDrag();

                        if (subject && subject.card_id) {
                            this.unplace(subject);
                        }
                    },

                    onDragOver(klass, slot) {
                        if (! this.drag) {
                            return;
                        }

                        this.hover = klass.id + ':' + slot.key;
                    },

                    clearHover(klass, slot) {
                        if (this.hover === klass.id + ':' + slot.key) {
                            this.hover = null;
                        }
                    },

                    onDrop(klass, slot) {
                        const subject = this.drag;

                        this.endDrag();

                        if (subject) {
                            this.moveTo(subject, klass, slot);
                        }
                    },

                    onSlotClick(klass, slot) {
                        if (this.selected) {
                            this.moveTo(this.selected, klass, slot);
                        }
                    },

                    // ---------------------------------------------------------------
                    // Shading
                    // ---------------------------------------------------------------
                    async loadCandidates(subject) {
                        const token = ++this.dragToken;
                        this.verdicts = {};
                        this.candidatesState = 'loading';
                        const params = new URLSearchParams({ setting_id: this.grid.setting.id });

                        if (subject.card_id) {
                            params.set('card_id', subject.card_id);
                        } else {
                            params.set('lesson_id', subject.lesson_id);
                        }

                        try {
                            const response = await fetch(this.endpoints.candidates + '?' + params.toString(), {
                                headers: { Accept: 'application/json' },
                            });

                            if (! response.ok) {
                                throw new Error('Availability request failed');
                            }

                            const data = await response.json();

                            // A second pick-up may have overtaken this one in flight.
                            if (token !== this.dragToken) {
                                return;
                            }

                            const verdicts = {};

                            data.slots.forEach((entry) => {
                                verdicts[entry.day + ':' + entry.period] = entry;
                            });

                            this.verdicts = verdicts;
                            this.candidatesState = 'ready';
                        } catch (error) {
                            if (token === this.dragToken) this.candidatesState = 'error';
                            // Shading is an aid; the drop itself is still checked server-side.
                        }
                    },

                    verdictFor(slot) {
                        return this.verdicts[slot.key] || null;
                    },

                    /** Is this class one the held card actually belongs to? */
                    ownsHeldCard(klass) {
                        const subject = this.drag || this.selected;

                        return subject ? subject.class_ids.includes(klass.id) : false;
                    },

                    /** The slots a held card would occupy if dropped here — a double covers two. */
                    coversSlot(klass, slot) {
                        const subject = this.drag || this.selected;

                        if (! subject || ! this.hover) {
                            return false;
                        }

                        const [hoverClass, hoverDay, hoverPeriod] = this.hover.split(':').map(Number);

                        return hoverClass === klass.id
                            && hoverDay === slot.day
                            && slot.period >= hoverPeriod
                            && slot.period < hoverPeriod + subject.span;
                    },

                    slotClass(klass, slot) {
                        const subject = this.drag || this.selected;

                        if (! subject) {
                            return 'hover:bg-slate-50 dark:hover:bg-brand-700/40';
                        }

                        if (! this.ownsHeldCard(klass)) {
                            return 'bg-slate-100 dark:bg-brand-900/60';
                        }

                        const state = this.indicatorState(slot);
                        const covered = this.coversSlot(klass, slot);
                        if (state === 'loading') return 'bg-amber-100/70 dark:bg-amber-900/40';

                        if (state === 'clash') {
                            return covered
                                ? 'bg-rose-400/80 dark:bg-rose-600/80'
                                : 'bg-rose-200/70 dark:bg-rose-900/50';
                        }

                        if (state !== 'available') return 'bg-slate-100 dark:bg-slate-800';

                        return covered
                            ? 'bg-emerald-400/70 dark:bg-emerald-600/70'
                            : 'bg-emerald-100/70 dark:bg-emerald-900/40';
                    },

                    slotTitle(klass, slot) {
                        const subject = this.drag || this.selected;

                        if (! subject || ! this.ownsHeldCard(klass)) {
                            return slot.title;
                        }

                        return this.periodTitle(slot);
                    },

                    // ---------------------------------------------------------------
                    // Moves
                    // ---------------------------------------------------------------
                    async moveTo(subject, klass, slot) {
                        if (this.busy) {
                            return;
                        }

                        if (! subject.class_ids.includes(klass.id)) {
                            this.say('error', subject.subject + ' belongs to ' + subject.class_names
                                + ', so it cannot go on the ' + klass.name + ' row.');

                            return;
                        }

                        const verdict = this.verdictFor(slot);

                        // Bounce on red without troubling the server — but the server still
                        // re-checks anything we do send, so a stale verdict cannot get through.
                        if (verdict && ! verdict.ok) {
                            this.say('error', this.reasons(verdict.conflicts));

                            return;
                        }

                        const payload = {
                            setting_id: this.grid.setting.id,
                            day: slot.day,
                            period: slot.period,
                            room_id: verdict ? verdict.room_id : null,
                        };

                        if (subject.card_id) {
                            payload.card_id = subject.card_id;
                        } else {
                            payload.lesson_id = subject.lesson_id;
                        }

                        const data = await this.post(this.endpoints.move, payload);

                        if (! data) {
                            return;
                        }

                        this.applyGrid(data.grid);
                        this.say('success', subject.subject + ' placed on ' + this.dayLabel(slot.day)
                            + ', period ' + slot.period + '.');

                        // Follow the card to its new home so a lock or a second move is one
                        // click away, and refresh the shading from where it now sits.
                        this.selected = data.placement
                            ? Object.assign({}, subject, {
                                card_id: data.placement.card_ids[0],
                                day: data.placement.day,
                                period: data.placement.period,
                                locked: data.placement.locked,
                            })
                            : null;

                        if (this.selected) {
                            this.loadCandidates(this.selected);
                        }
                    },

                    async unplace(subject) {
                        const data = await this.post(this.endpoints.unplace, {
                            setting_id: this.grid.setting.id,
                            card_id: subject.card_id,
                        });

                        if (! data) {
                            return;
                        }

                        this.applyGrid(data.grid);
                        this.clearSelection();
                        this.say('success', subject.subject + ' is back in the tray.');
                    },

                    async toggleLock(subject) {
                        const data = await this.post(this.endpoints.lock, {
                            setting_id: this.grid.setting.id,
                            card_id: subject.card_id,
                            locked: ! subject.locked,
                        });

                        if (! data) {
                            return;
                        }

                        this.applyGrid(data.grid);
                        this.selected = Object.assign({}, subject, { locked: data.placement.locked });
                        this.say('success', subject.subject + (data.placement.locked ? ' locked.' : ' unlocked.'));
                    },

                    applyGrid(payload) {
                        this.grid = payload;
                        if (this.inspected) {
                            const card = payload.cards.find(card => card.card_ids.includes(this.inspected.card_id));
                            const item = payload.tray.find(item => item.lesson_id === this.inspected.lesson_id);
                            this.inspected = card ? this.gridCard(card) : (item ? this.trayCard(item) : null);
                        }
                        this.verdicts = {};
                        this.hover = null;
                        this.reindex();
                        this.syncRoomDrafts();
                        this.syncDivisionDrafts();
                    },

                    async post(url, body, method = 'POST') {
                        this.busy = true;

                        try {
                            const response = await fetch(url, {
                                method,
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(body),
                            });

                            const data = await response.json().catch(() => null);

                            if (response.ok) {
                                return data;
                            }

                            const firstError = data && data.errors
                                ? Object.values(data.errors).flat()[0]
                                : null;
                            this.say('error', firstError || (data && data.message) || 'That change was refused.');

                            return null;
                        } catch (error) {
                            this.say('error', 'The move could not be saved — check your connection and try again.');

                            return null;
                        } finally {
                            this.busy = false;
                        }
                    },

                    // ---------------------------------------------------------------
                    // Presentation
                    // ---------------------------------------------------------------
                    dayColour(day) {
                        const hues = [210, 38, 160, 275, 345, 185];
                        const index = this.grid.days.findIndex(entry => Number(entry.number) === Number(day));
                        return { '--tt-day-hue': hues[Math.max(0, index) % hues.length] };
                    },

                    colourOf(subject) {
                        if (subject.colour) {
                            return subject.colour;
                        }

                        const code = String(subject.colour_key || 'teacher-unassigned');
                        const teacherId = Number(code.replace(/^teacher-/, ''));

                        if (Number.isInteger(teacherId) && teacherId > 0) {
                            // Multiplying by seven walks across the deliberately interleaved
                            // palette instead of putting adjacent database ids beside each other.
                            return teacherPalette[(teacherId * 7) % teacherPalette.length];
                        }

                        let hash = 2166136261;

                        for (let i = 0; i < code.length; i++) {
                            hash ^= code.charCodeAt(i);
                            hash = Math.imul(hash, 16777619);
                        }

                        return teacherPalette[(hash >>> 0) % teacherPalette.length];
                    },

                    missingTeacher(card) {
                        return ! Array.isArray(card.teacher_ids) || card.teacher_ids.length === 0;
                    },

                    missingRoom(card) {
                        if ((card.card_id !== null && card.card_id !== undefined)
                            || (Array.isArray(card.card_ids) && card.card_ids.length > 0)) {
                            return card.room_id === null || card.room_id === undefined;
                        }

                        if (Array.isArray(card.room_ids) && card.room_ids.length > 0) {
                            return false;
                        }

                        return ! this.baseRoomFor(card);
                    },

                    /** Rec. 709 luma — deep teacher colours need light text. */
                    inkOn(colour) {
                        const match = /^#?([0-9a-f]{6})/i.exec(String(colour).trim());

                        if (! match) {
                            return '#111827';
                        }

                        const value = parseInt(match[1], 16);
                        const luma = 0.2126 * ((value >> 16) & 255)
                            + 0.7152 * ((value >> 8) & 255)
                            + 0.0722 * (value & 255);

                        return luma > 140 ? '#111827' : '#F9FAFB';
                    },

                    swatch(subject) {
                        const colour = this.colourOf(subject);

                        return { backgroundColor: colour, color: this.inkOn(colour) };
                    },

                    cardStack(card, classId) {
                        const peers = this.cardsFor(classId)
                            .filter((other) => other.day === card.day
                                && other.period < card.period + card.span
                                && card.period < other.period + other.span)
                            .sort((left, right) => String(left.key).localeCompare(String(right.key)));
                        const index = Math.max(0, peers.findIndex((other) => other.key === card.key));

                        return { index, count: Math.max(1, peers.length) };
                    },

                    optionShare(card, classId) {
                        const groups = (card.groups || []).filter(group => Number(group.class_id) === Number(classId));
                        const klass = this.grid.classes.find(entry => Number(entry.id) === Number(classId));
                        if (!groups.length || groups.some(group => group.entire_class)) return { count: 1, index: 0, size: 1 };
                        const division = klass?.divisions?.find(entry => entry.id === groups[0].division_id);
                        if (!division?.groups.length) return { count: Math.max(2, this.cardStack(card, classId).count), index: this.cardStack(card, classId).index, size: 1 };
                        const indices = division.groups.map((group, index) => groups.some(entry => entry.id === group.id) ? index : -1).filter(index => index >= 0);
                        return { count: division.groups.length, index: indices.length ? Math.min(...indices) : 0, size: Math.max(1, indices.length) };
                    },

                    trayShare(item) {
                        return this.optionShare(item, Number(this.classFilter) || item.class_ids[0]);
                    },

                    projectedCapacity(classId) {
                        let whole = 0;
                        const divisions = {};
                        const add = (periods, groups) => {
                            if (!groups.length || groups.some(group => group.entire_class)) { whole += periods; return; }
                            for (const group of groups) {
                                const division = this.groupsForClass(classId).find(entry => entry.id === Number(group.id))?.division_id ?? 'group-' + group.id;
                                divisions[division] ||= {};
                                divisions[division][group.id] = (divisions[division][group.id] || 0) + periods;
                            }
                        };
                        for (const lesson of this.grid.editor.lesson_presets || []) {
                            if (!lesson.class_ids.includes(Number(classId)) || (this.editor.mode === 'edit' && lesson.lesson_id === this.editor.subject?.lesson_id)) continue;
                            add(lesson.cards_per_cycle * lesson.periods_per_card, lesson.groups.filter(group => Number(group.class_id) === Number(classId)));
                        }
                        const rows = this.editor.form.attendance.filter(row => Number(row.class_id) === Number(classId));
                        if (rows.length) add(Number(this.editor.form.cards_per_cycle) * Number(this.editor.form.periods_per_card), rows.map(row => ({ id: Number(row.group_id), entire_class: !row.group_id })));
                        return whole + Object.values(divisions).reduce((sum, groups) => sum + Math.max(...Object.values(groups)), 0);
                    },

                    exceedsCapacity() {
                        return this.uniqueAttendanceClassIds().some(id => this.projectedCapacity(id) > this.grid.setting.capacity);
                    },

                    capacityLabel(classId) {
                        const klass = this.grid.classes.find(entry => Number(entry.id) === Number(classId));
                        const limit = this.grid.setting.capacity ?? this.grid.days.length * this.grid.periods.length;
                        return (klass?.periods_used || 0) + ' / ' + limit + ' periods';
                    },

                    cardStyle(card, classId) {
                        const index = this.slotIndex[card.day + ':' + card.period];
                        const endSlot = this.slotList[index + Number(card.span) - 1];
                        const edgeWidth = endSlot?.edgeClass === 'tt-day-divider' ? 4
                            : (endSlot?.edgeClass === 'tt-midday-divider' ? 3 : 1);
                        const colour = this.colourOf(card);
                        const stack = this.cardStack(card, classId);
                        const share = this.optionShare(card, classId);
                        const visualLanes = share.count > 1 ? share.count : stack.count;
                        const lane = share.count > 1 ? share.index : stack.index;
                        const size = share.count > 1 ? share.size : 1;

                        return {
                            gridColumn: (index + 1) + ' / span ' + card.span,
                            gridRow: '1',
                            marginRight: edgeWidth + 'px',
                            alignSelf: 'start',
                            height: 'calc(100% * ' + size + ' / ' + visualLanes + ' - 3px)',
                            top: 'calc(100% * ' + lane + ' / ' + visualLanes + ' + 1px)',
                            position: 'relative',
                            marginLeft: '1px',
                            borderRadius: '4px',
                            boxShadow: 'inset 0 1px 0 rgb(255 255 255 / 35%), 0 1px 2px rgb(15 23 42 / 18%)',
                            backgroundColor: colour,
                            color: this.inkOn(colour),
                        };
                    },

                    groupLabel(subject) {
                        const named = (subject.groups || []).filter((group) => ! group.entire_class);

                        return named.length ? named.map((group) => group.name).join(' / ') : 'whole class';
                    },

                    describe(subject) {
                        const parts = [subject.subject, subject.class_names, this.groupLabel(subject)];

                        if (subject.teachers.length) {
                            parts.push(subject.teachers.join(', '));
                        }

                        if (subject.room) {
                            parts.push(subject.room);
                        }

                        if (subject.span > 1) {
                            parts.push('double');
                        }

                        if (subject.day) {
                            parts.push(this.dayLabel(subject.day) + ' P' + subject.period);
                        }

                        return parts.join(' · ');
                    },

                    cardTitle(card) {
                        return this.describe(this.gridCard(card)) + (card.locked ? ' · locked' : '');
                    },

                    trayLine(item) {
                        const parts = [item.class_names, this.groupLabel(item)];

                        if (item.teachers.length) {
                            parts.push(item.teachers.join(', '));
                        }

                        return parts.join(' · ');
                    },

                    trayRoomLine(item) {
                        const rooms = (item.room_ids || []).map((id) =>
                            this.grid.editor.rooms.find((room) => room.id === id)?.name
                        ).filter(Boolean);

                        if (rooms.length > 0) {
                            return 'Room chosen per card from: ' + rooms.join(', ');
                        }

                        const baseRoom = this.baseRoomFor(item);

                        return baseRoom ? 'Automatic baseroom: ' + baseRoom.name : 'Cards may be placed without a room';
                    },

                    dayLabel(number) {
                        const day = this.grid.days.find((entry) => entry.number === number);

                        return day ? day.label : 'Day ' + number;
                    },

                    reasons(conflicts) {
                        if (! conflicts || ! conflicts.length) {
                            return 'That slot will not take this card.';
                        }

                        return conflicts.map((conflict) => conflict.message).join(' ');
                    },

                    say(kind, text) {
                        this.message = { kind, text };
                    },
                };
            };
        </script>
    @endpush
</x-app-layout>
