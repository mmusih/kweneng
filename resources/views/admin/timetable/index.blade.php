<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 rounded-2xl bg-gradient-to-r from-[#212A31] via-[#124E66] to-[#2E3944] p-6 shadow-md">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-[#D3D9D4]">Academic planning</p>
                    <h2 class="mt-1 text-2xl font-semibold text-white">School timetable</h2>
                    <p class="mt-1 text-sm text-[#D3D9D4]">Build the day structure, option groups, rooms, and lessons before publishing.</p>
                </div>
                @if ($template)
                    <form method="POST" action="{{ route('admin.timetable.templates.publish', $template) }}">
                        @csrf
                        <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-[#124E66] shadow-sm hover:bg-gray-100">
                            {{ $template->is_published ? 'Republish changes' : 'Publish timetable' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Please correct the following:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Create a timetable cycle</h3>
                <p class="mt-1 text-sm text-gray-500">Use a normal school week or a rotating numbered-day cycle.</p>
                <form method="POST" action="{{ route('admin.timetable.templates.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <label class="sm:col-span-2 text-sm font-medium text-gray-700">
                        Name
                        <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-gray-300" placeholder="2026 Main Timetable">
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Academic year
                        <select name="academic_year_id" required class="mt-1 w-full rounded-lg border-gray-300">
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $academicYear?->id) == $year->id)>
                                    {{ $year->year_name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Cycle
                        <select name="cycle_type" id="cycle-type" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="weekly" @selected(old('cycle_type') === 'weekly')>Weekly</option>
                            <option value="rotating" @selected(old('cycle_type') === 'rotating')>Rotating cycle</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Number of days
                        <input id="cycle-length" type="number" name="cycle_length" value="{{ old('cycle_length', 5) }}" min="1" max="7" required class="mt-1 w-full rounded-lg border-gray-300">
                    </label>
                    <div id="cycle-start-fields" class="hidden sm:col-span-2 grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium text-gray-700">
                            First school date
                            <input type="date" name="cycle_start_date" value="{{ old('cycle_start_date') }}" class="mt-1 w-full rounded-lg border-gray-300">
                        </label>
                        <label class="text-sm font-medium text-gray-700">
                            Day on that date
                            <input id="cycle-start-day" type="number" name="cycle_start_day_number" value="{{ old('cycle_start_day_number', 1) }}" min="1" max="{{ old('cycle_length', 5) }}" class="mt-1 w-full rounded-lg border-gray-300">
                        </label>
                    </div>
                    <button class="sm:col-span-2 rounded-lg bg-[#124E66] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0f4054]">
                        Create cycle
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Working timetable</h3>
                        @if ($template)
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $template->name }} · {{ $template->academicYear?->year_name }} ·
                                {{ ucfirst($template->cycle_type) }}
                            </p>
                        @else
                            <p class="mt-1 text-sm text-gray-500">Create your first cycle to start scheduling.</p>
                        @endif
                    </div>
                    @if ($templates->isNotEmpty())
                        <form method="GET">
                            <select name="template_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm">
                                @foreach ($templates as $option)
                                    <option value="{{ $option->id }}" @selected($template?->id === $option->id)>
                                        {{ $option->name }}{{ $option->is_active ? ' — active' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
                @if ($template)
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-xl bg-sky-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-sky-700">Days</p>
                            <p class="mt-1 text-xl font-semibold text-sky-950">{{ $template->days->count() }}</p>
                        </div>
                        <div class="rounded-xl bg-violet-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-violet-700">Lessons</p>
                            <p class="mt-1 text-xl font-semibold text-violet-950">{{ $template->days->sum(fn ($day) => $day->entries->count()) }}</p>
                        </div>
                        <div class="rounded-xl bg-amber-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-amber-700">Groups</p>
                            <p class="mt-1 text-xl font-semibold text-amber-950">{{ $groups->count() }}</p>
                        </div>
                        <div class="rounded-xl {{ $template->is_published ? 'bg-emerald-50' : 'bg-gray-100' }} p-3">
                            <p class="text-xs uppercase tracking-wide {{ $template->is_published ? 'text-emerald-700' : 'text-gray-600' }}">Status</p>
                            <p class="mt-1 font-semibold {{ $template->is_published ? 'text-emerald-950' : 'text-gray-800' }}">
                                {{ $template->is_published ? 'Published' : 'Draft' }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @if ($template)
            @if ($template->cycle_type === \App\Models\TimetableTemplate::CYCLE_ROTATING)
                @php($todayCycleDay = $template->dayForDate(today()))
                <section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-cyan-700">Rotating-cycle control</p>
                            <h3 class="mt-1 text-lg font-semibold text-cyan-950">Set the cycle day after a break</h3>
                            <p class="mt-1 max-w-2xl text-sm text-cyan-800">
                                Choose a school date and declare which cycle day it is. Saturdays and Sundays are skipped automatically, and the sequence continues from the latest dated setting.
                            </p>
                        </div>
                        <div class="rounded-xl bg-white px-4 py-3 text-sm ring-1 ring-cyan-200">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Today · {{ today()->format('d M Y') }}</p>
                            <p class="mt-1 font-semibold text-cyan-950">
                                {{ $todayCycleDay?->name ?? (today()->isWeekend() ? 'Weekend — no cycle day' : 'No cycle day set') }}
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.timetable.cycle-anchors.store', $template) }}" class="mt-5 grid gap-3 md:grid-cols-[1fr_1fr_1.5fr_auto] md:items-end">
                        @csrf
                        <label class="text-sm font-medium text-gray-700">
                            School date
                            <input type="date" name="anchor_date" value="{{ old('anchor_date', today()->toDateString()) }}" required class="mt-1 w-full rounded-lg border-cyan-200 bg-white">
                        </label>
                        <label class="text-sm font-medium text-gray-700">
                            Cycle day
                            <select name="day_number" required class="mt-1 w-full rounded-lg border-cyan-200 bg-white">
                                @foreach ($template->days as $day)
                                    <option value="{{ $day->day_number }}" @selected(old('day_number') == $day->day_number)>{{ $day->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-medium text-gray-700">
                            Reason or note
                            <input name="note" value="{{ old('note') }}" class="mt-1 w-full rounded-lg border-cyan-200 bg-white" placeholder="e.g. Resume after mid-term break">
                        </label>
                        <button class="rounded-lg bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800">
                            Set cycle day
                        </button>
                    </form>

                    <div class="mt-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-cyan-800">Cycle history</p>
                        <div class="mt-2 space-y-2">
                            @foreach ($template->cycleAnchors->sortByDesc('anchor_date') as $anchor)
                                <div class="flex flex-col gap-2 rounded-lg bg-white px-3 py-2 text-sm ring-1 ring-cyan-100 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <span class="font-semibold text-gray-900">{{ $anchor->anchor_date->format('d M Y') }}</span>
                                        <span class="text-gray-600">is Cycle Day {{ $anchor->day_number }}</span>
                                        @if ($anchor->note)
                                            <span class="text-gray-500">· {{ $anchor->note }}</span>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('admin.timetable.cycle-anchors.destroy', [$template, $anchor]) }}" onsubmit="return confirm('Remove this cycle setting?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">1. School-day structure</h3>
                    <p class="mt-1 text-sm text-gray-500">Add lessons, breaks, lunch, assembly, and other non-teaching blocks to each day.</p>
                </div>
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($template->days as $day)
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-gray-900">{{ $day->name }}</h4>
                                <span class="text-xs text-gray-500">{{ $day->periods->count() }} blocks</span>
                            </div>
                            <div class="mt-3 space-y-2">
                                @forelse ($day->periods as $period)
                                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                        <span>
                                            <span class="font-semibold text-gray-800">{{ $period->sequence }}. {{ $period->name }}</span>
                                            <span class="ml-1 text-gray-500">{{ substr($period->start_time, 0, 5) }}–{{ substr($period->end_time, 0, 5) }}</span>
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-xs {{ $period->type === 'lesson' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ ucfirst($period->type) }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">No periods yet.</p>
                                @endforelse
                            </div>
                            <form method="POST" action="{{ route('admin.timetable.periods.store', [$template, $day]) }}" class="mt-4 grid grid-cols-2 gap-2">
                                @csrf
                                <input type="number" name="sequence" min="1" max="50" value="{{ $day->periods->max('sequence') + 1 }}" required class="rounded-lg border-gray-300 text-sm" placeholder="Order">
                                <input name="name" required class="rounded-lg border-gray-300 text-sm" placeholder="Period 1 / Break">
                                <input type="time" name="start_time" required class="rounded-lg border-gray-300 text-sm">
                                <input type="time" name="end_time" required class="rounded-lg border-gray-300 text-sm">
                                <select name="type" class="rounded-lg border-gray-300 text-sm">
                                    @foreach (\App\Models\TimetablePeriod::TYPES as $type)
                                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-semibold text-white">Add block</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">2. Teaching rooms</h3>
                    <form method="POST" action="{{ route('admin.timetable.rooms.store') }}" class="mt-4 grid grid-cols-2 gap-3">
                        @csrf
                        <input name="name" required class="rounded-lg border-gray-300 text-sm" placeholder="Room name">
                        <input name="code" class="rounded-lg border-gray-300 text-sm" placeholder="Code (optional)">
                        <input type="number" min="1" name="capacity" class="rounded-lg border-gray-300 text-sm" placeholder="Capacity">
                        <input name="type" class="rounded-lg border-gray-300 text-sm" placeholder="Classroom / Lab">
                        <button class="col-span-2 rounded-lg bg-[#124E66] px-4 py-2 text-sm font-semibold text-white">Add room</button>
                    </form>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse ($rooms as $room)
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700">
                                {{ $room->name }}{{ $room->code ? " · {$room->code}" : '' }}
                            </span>
                        @empty
                            <p class="text-sm text-gray-500">Rooms are optional, but useful for detecting room clashes.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">3. Split and option groups</h3>
                    <p class="mt-1 text-sm text-gray-500">Combine selected students from one or more classes for an option subject.</p>
                    <form method="POST" action="{{ route('admin.timetable.groups.store') }}" class="mt-4 grid grid-cols-2 gap-3">
                        @csrf
                        <input type="hidden" name="academic_year_id" value="{{ $template->academic_year_id }}">
                        <input name="name" required class="rounded-lg border-gray-300 text-sm" placeholder="Group name">
                        <input name="code" class="rounded-lg border-gray-300 text-sm" placeholder="Code">
                        <select name="subject_id" required class="col-span-2 rounded-lg border-gray-300 text-sm">
                            <option value="">Select subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Classes
                            <select name="class_ids[]" required multiple size="5" class="mt-1 w-full rounded-lg border-gray-300 text-sm normal-case">
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Students
                            <select name="student_ids[]" required multiple size="5" class="mt-1 w-full rounded-lg border-gray-300 text-sm normal-case">
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}">{{ $student->user?->name }} · {{ $student->currentClass?->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <textarea name="description" class="col-span-2 rounded-lg border-gray-300 text-sm" rows="2" placeholder="Description (optional)"></textarea>
                        <button class="col-span-2 rounded-lg bg-[#124E66] px-4 py-2 text-sm font-semibold text-white">Create group</button>
                    </form>
                    @if ($groups->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            @foreach ($groups as $group)
                                <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-950">
                                    <span class="font-semibold">{{ $group->name }}</span> · {{ $group->subject?->name }} ·
                                    {{ $group->students->count() }} students from {{ $group->classes->pluck('name')->join(', ') }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">4. Schedule a lesson</h3>
                <p class="mt-1 text-sm text-gray-500">Choose either a full class or a split group. Multi-period ranges create double or extended lessons.</p>
                @if ($unscheduledAssignments->isNotEmpty())
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-950">
                            {{ $unscheduledAssignments->count() }} teaching assignment{{ $unscheduledAssignments->count() === 1 ? '' : 's' }} not yet represented
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($unscheduledAssignments as $assignment)
                                <span class="rounded-full bg-white px-3 py-1 text-xs text-amber-900 ring-1 ring-amber-200">
                                    {{ $assignment->class?->name }} · {{ $assignment->subject?->name }} · {{ $assignment->teacher?->user?->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        Every current teacher/subject/class assignment is represented in this timetable.
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.timetable.entries.store', $template) }}" class="mt-5 grid gap-3 md:grid-cols-3">
                    @csrf
                    <label class="text-sm font-medium text-gray-700">
                        Day
                        <select name="timetable_day_id" id="entry-day" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select day</option>
                            @foreach ($template->days as $day)
                                <option value="{{ $day->id }}">{{ $day->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Starts
                        <select name="start_period_id" id="entry-start" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select start</option>
                            @foreach ($template->days as $day)
                                @foreach ($day->periods->where('type', 'lesson') as $period)
                                    <option value="{{ $period->id }}" data-day="{{ $day->id }}">{{ $period->name }} · {{ substr($period->start_time, 0, 5) }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Ends
                        <select name="end_period_id" id="entry-end" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select end</option>
                            @foreach ($template->days as $day)
                                @foreach ($day->periods->where('type', 'lesson') as $period)
                                    <option value="{{ $period->id }}" data-day="{{ $day->id }}">{{ $period->name }} · {{ substr($period->end_time, 0, 5) }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Full class
                        <select name="class_id" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Not a full-class lesson</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Or split group
                        <select name="timetable_group_id" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Not a split-group lesson</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Subject
                        <select name="subject_id" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select subject</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Teacher
                        <select name="teacher_id" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select teacher</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->user?->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Room
                        <select name="timetable_room_id" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">No room assigned</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium text-gray-700">
                        Display title
                        <input name="title" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Optional">
                    </label>
                    <label class="md:col-span-3 text-sm font-medium text-gray-700">
                        Notes
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border-gray-300" placeholder="Optional instructions"></textarea>
                    </label>
                    <button class="md:col-span-3 rounded-lg bg-[#124E66] px-4 py-2.5 text-sm font-semibold text-white">Add lesson with clash checks</button>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Scheduled lessons</h3>
                <div class="mt-4 space-y-5">
                    @foreach ($template->days as $day)
                        <div>
                            <h4 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $day->name }}</h4>
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th class="px-3 py-2">Time</th>
                                            <th class="px-3 py-2">Lesson</th>
                                            <th class="px-3 py-2">Class / group</th>
                                            <th class="px-3 py-2">Teacher</th>
                                            <th class="px-3 py-2">Room</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($day->entries->sortBy(fn ($entry) => $entry->startPeriod?->sequence) as $entry)
                                            <tr>
                                                <td class="whitespace-nowrap px-3 py-2 text-gray-600">
                                                    {{ substr($entry->startPeriod?->start_time, 0, 5) }}–{{ substr($entry->endPeriod?->end_time, 0, 5) }}
                                                </td>
                                                <td class="px-3 py-2 font-medium text-gray-900">{{ $entry->title ?: $entry->subject?->name }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $entry->class?->name ?: $entry->group?->name }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $entry->teacher?->user?->name }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $entry->room?->name ?: '—' }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    <form method="POST" action="{{ route('admin.timetable.entries.destroy', [$template, $entry]) }}" onsubmit="return confirm('Remove this lesson?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="px-3 py-4 text-center text-gray-500">No lessons scheduled.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <script>
        const cycleType = document.getElementById('cycle-type');
        const cycleStart = document.getElementById('cycle-start-fields');
        const cycleLength = document.getElementById('cycle-length');
        const cycleStartDay = document.getElementById('cycle-start-day');
        const toggleCycleStart = () => {
            const rotating = cycleType?.value === 'rotating';
            cycleStart?.classList.toggle('hidden', !rotating);
            cycleStart?.classList.toggle('grid', rotating);
        };
        const updateCycleDayMaximum = () => {
            if (cycleStartDay && cycleLength) {
                cycleStartDay.max = cycleLength.value;
                if (Number(cycleStartDay.value) > Number(cycleLength.value)) {
                    cycleStartDay.value = cycleLength.value;
                }
            }
        };
        cycleType?.addEventListener('change', toggleCycleStart);
        cycleLength?.addEventListener('input', updateCycleDayMaximum);
        toggleCycleStart();
        updateCycleDayMaximum();

        const entryDay = document.getElementById('entry-day');
        const filterPeriods = () => {
            ['entry-start', 'entry-end'].forEach((id) => {
                const select = document.getElementById(id);
                if (!select) return;
                [...select.options].forEach((option, index) => {
                    option.hidden = index > 0 && option.dataset.day !== entryDay.value;
                });
                select.value = '';
            });
        };
        entryDay?.addEventListener('change', filterPeriods);
        filterPeriods();
    </script>
</x-app-layout>
