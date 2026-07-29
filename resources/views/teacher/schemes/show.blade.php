<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">{{ $scheme->title }}</h2>
                    <p class="text-white/80 text-sm mt-1">
                        {{ $scheme->teacherSubject?->class?->name }} · {{ $scheme->teacherSubject?->subject?->name }} · {{ $scheme->academicYear?->year_name }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('teacher.schemes.index') }}" class="inline-flex items-center px-4 py-2 bg-white/15 hover:bg-white/25 rounded-lg text-white text-sm font-semibold transition">Back</a>
                    @if (!in_array($scheme->status, ['submitted', 'approved', 'active']))
                        <form method="POST" action="{{ route('teacher.schemes.submit', $scheme) }}">
                            @csrf
                            <button class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 rounded-lg text-sm font-semibold shadow-sm hover:bg-emerald-50 transition">Submit to HOD</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $overallPct = $scheme->completionPct();
        $expectedPct = $scheme->expectedPct();
        $pacing = $scheme->pacingStatus();
        $statusLabels = $statuses;
    @endphp

    <div class="py-8 kw-soft-section min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-red-800">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($scheme->review_comment)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    <div class="font-semibold">HOD Review Comment</div>
                    <div class="text-sm mt-1">{{ $scheme->review_comment }}</div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Syllabus Completion</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2">{{ $overallPct }}%</h3>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden mt-3">
                        <div class="h-2 bg-emerald-500" style="width: {{ min($overallPct, 100) }}%"></div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Expected by Now</p>
                    <h3 class="text-3xl font-bold text-indigo-600 mt-2">{{ $expectedPct }}%</h3>
                    <p class="text-sm text-gray-500 mt-3">Calculated from term dates and planned weeks.</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Pacing Badge</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ str_replace('_', ' ', ucfirst($pacing)) }}</h3>
                    <p class="text-sm text-gray-500 mt-3">Actual progress compared to expected progress.</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Review Status</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ str_replace('_', ' ', ucfirst($scheme->status)) }}</h3>
                    <p class="text-sm text-gray-500 mt-3">Submit when your yearly plan is ready.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Scheme Planner</h3>
                        <p class="text-sm text-gray-500">Drag topics from the bank into the correct term and week, then save the plan.</p>
                    </div>
                    <button id="save-plan-btn" type="button" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Save Plan</button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] min-h-[520px]">
                    <div class="p-4 lg:p-6 space-y-5 bg-gray-50/60">
                        @foreach ($terms as $term)
                            @php
                                $weeks = $term->start_date && $term->end_date ? max(1, $term->start_date->diffInWeeks($term->end_date) + 1) : 13;
                            @endphp
                            <div class="rounded-xl bg-white border border-gray-100 shadow-sm overflow-hidden" data-term="{{ $term->id }}">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ $term->name }}</h4>
                                        <p class="text-xs text-gray-500">
                                            {{ $term->start_date?->format('d M Y') ?? 'No start date' }} — {{ $term->end_date?->format('d M Y') ?? 'No end date' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @for ($week = 1; $week <= $weeks; $week++)
                                        @php $key = $term->id . '-' . $week; @endphp
                                        <div class="grid grid-cols-[80px_1fr] min-h-[54px]">
                                            <div class="bg-gray-50 px-3 py-3 text-sm font-semibold text-gray-600 border-r border-gray-100">Week {{ $week }}</div>
                                            <div class="week-drop p-2 flex flex-wrap gap-2 items-start min-h-[54px]" data-term-id="{{ $term->id }}" data-week="{{ $week }}">
                                                @foreach (($plannedItems[$key] ?? collect())->sortBy('planned_order') as $item)
                                                    @include('teacher.schemes.partials.topic-chip', ['item' => $item])
                                                @endforeach
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-l border-gray-100 bg-white p-4 lg:p-6 space-y-6">
                        <div>
                            <h4 class="font-semibold text-gray-900">Topic Bank</h4>
                            <p class="text-sm text-gray-500 mt-1">Unplanned topics stay here until dragged into a week.</p>
                            <div id="topic-bank" class="mt-4 space-y-2 min-h-[120px] p-2 rounded-lg border border-dashed border-gray-200" data-term-id="" data-week="">
                                @foreach ($bankItems as $item)
                                    @include('teacher.schemes.partials.topic-chip', ['item' => $item])
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-5">
                            <h4 class="font-semibold text-gray-900">Add Topic Manually</h4>
                            <form method="POST" action="{{ route('teacher.schemes.topics.store', $scheme) }}" class="mt-3 space-y-3">
                                @csrf
                                <input name="title" required placeholder="Topic title" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                <textarea name="subtopics" rows="3" placeholder="Optional subtopics, one per line" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                                <button class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-semibold hover:bg-gray-800">Add to Bank</button>
                            </form>
                        </div>

                        <div class="border-t border-gray-100 pt-5">
                            <h4 class="font-semibold text-gray-900">Paste Syllabus Text</h4>
                            <p class="text-xs text-gray-500 mt-1">Put main topics on separate lines. Start subtopics with - or •.</p>
                            <form method="POST" action="{{ route('teacher.schemes.import-text', $scheme) }}" class="mt-3 space-y-3">
                                @csrf
                                <textarea name="raw_text" rows="7" placeholder="Algebra&#10;- Expanding brackets&#10;- Factorisation&#10;Geometry&#10;- Angles" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                                <button class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Import Text</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Completion Tracker</h3>
                    <p class="text-sm text-gray-500">Update progress quickly after teaching. HODs will see these updates in their dashboard.</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($scheme->items->whereNotNull('term_id')->sortBy([['term_id', 'asc'], ['week_number', 'asc'], ['planned_order', 'asc']]) as $item)
                        <div class="p-5">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">{{ $item->term?->name }} · Week {{ $item->week_number }}</span>
                                        @if ($item->isBehindSchedule())
                                            <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 text-xs font-semibold">Behind</span>
                                        @endif
                                        <span class="px-2 py-1 rounded-full bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold">{{ $statusLabels[$item->status] ?? $item->status }}</span>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 mt-2">{{ $item->title }}</h4>
                                    @if ($item->teacher_comment)
                                        <p class="text-sm text-gray-500 mt-1">{{ $item->teacher_comment }}</p>
                                    @endif

                                    @if ($item->subtopics->count())
                                        <div class="mt-3 space-y-2">
                                            @foreach ($item->subtopics as $subtopic)
                                                <form method="POST" action="{{ route('teacher.schemes.items.subtopics.toggle', [$scheme, $item, $subtopic]) }}" class="inline-block mr-2 mb-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border {{ $subtopic->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                                                        <span>{{ $subtopic->status === 'completed' ? '✓' : '○' }}</span>
                                                        {{ $subtopic->title }}
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('teacher.schemes.items.status', [$scheme, $item]) }}" class="flex flex-col sm:flex-row gap-2 lg:w-[520px]">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                        @foreach ($statusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected($item->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input name="teacher_comment" value="{{ $item->teacher_comment }}" placeholder="Optional comment" class="flex-1 rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                    <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Update</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">No topics have been planned yet. Drag items from the topic bank into weeks above.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <style>
        .topic-chip { cursor: grab; }
        .topic-chip:active { cursor: grabbing; }
        .week-drop.drag-over, #topic-bank.drag-over { outline: 2px dashed #10b981; outline-offset: -3px; background: #ecfdf5; }
    </style>

    <script>
        const draggedClass = 'opacity-50';
        let dragged = null;

        function makeDraggable(el) {
            el.setAttribute('draggable', 'true');
            el.addEventListener('dragstart', function () {
                dragged = el;
                setTimeout(() => el.classList.add(draggedClass), 0);
            });
            el.addEventListener('dragend', function () {
                el.classList.remove(draggedClass);
                dragged = null;
            });
        }

        function makeDropzone(zone) {
            zone.addEventListener('dragover', function (e) {
                e.preventDefault();
                zone.classList.add('drag-over');
            });
            zone.addEventListener('dragleave', function () {
                zone.classList.remove('drag-over');
            });
            zone.addEventListener('drop', function (e) {
                e.preventDefault();
                zone.classList.remove('drag-over');
                if (dragged) zone.appendChild(dragged);
            });
        }

        document.querySelectorAll('.topic-chip').forEach(makeDraggable);
        document.querySelectorAll('.week-drop, #topic-bank').forEach(makeDropzone);

        document.getElementById('save-plan-btn')?.addEventListener('click', async function () {
            const items = [];

            document.querySelectorAll('.week-drop, #topic-bank').forEach(zone => {
                const termId = zone.dataset.termId || null;
                const week = zone.dataset.week || null;
                zone.querySelectorAll('.topic-chip').forEach((chip, index) => {
                    items.push({
                        id: Number(chip.dataset.itemId),
                        term_id: termId ? Number(termId) : null,
                        week_number: week ? Number(week) : null,
                        planned_order: index + 1,
                    });
                });
            });

            const button = this;
            button.disabled = true;
            button.textContent = 'Saving...';

            try {
                const response = await fetch(@json(route('teacher.schemes.plan.save', $scheme)), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: JSON.stringify({ items }),
                });

                if (!response.ok) throw new Error('Save failed');
                button.textContent = 'Saved';
                setTimeout(() => window.location.reload(), 600);
            } catch (error) {
                alert('The plan could not be saved. Please try again.');
                button.disabled = false;
                button.textContent = 'Save Plan';
            }
        });
    </script>
</x-app-layout>
