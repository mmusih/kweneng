<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">{{ $scheme->title }}</h2>
                    <p class="text-white/80 text-sm mt-1">
                        {{ $scheme->teacherSubject?->teacher?->user?->name }} · {{ $scheme->teacherSubject?->class?->name }} · {{ $scheme->teacherSubject?->subject?->name }}
                    </p>
                </div>
                <a href="{{ route($schemeRoutes['index']) }}" class="inline-flex items-center px-4 py-2 bg-white/15 hover:bg-white/25 rounded-lg text-white text-sm font-semibold transition">Back</a>
            </div>
        </div>
    </x-slot>

    @php
        $overallPct = $scheme->completionPct();
        $expectedPct = $scheme->expectedPct();
        $pacing = $scheme->pacingStatus();
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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Actual Completion</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2">{{ $overallPct }}%</h3>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Expected</p>
                    <h3 class="text-3xl font-bold text-indigo-600 mt-2">{{ $expectedPct }}%</h3>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Pacing</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ str_replace('_', ' ', ucfirst($pacing)) }}</h3>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Scheme Status</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ str_replace('_', ' ', ucfirst($scheme->status)) }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Planned Scheme and Progress</h3>
                        <p class="text-sm text-gray-500">Read-only HOD view. Completion ticks remain controlled by the assigned teacher.</p>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($terms as $term)
                            @php
                                $termItems = $scheme->items->where('term_id', $term->id)->sortBy([['week_number', 'asc'], ['planned_order', 'asc']]);
                            @endphp
                            <div class="p-6">
                                <h4 class="font-semibold text-gray-900">{{ $term->name }}</h4>
                                @if ($termItems->isEmpty())
                                    <p class="text-sm text-gray-500 mt-2">No topics planned for this term.</p>
                                @else
                                    <div class="mt-4 space-y-4">
                                        @foreach ($termItems->groupBy('week_number') as $week => $items)
                                            <div class="rounded-lg border border-gray-100 overflow-hidden">
                                                <div class="px-4 py-2 bg-gray-50 text-sm font-semibold text-gray-700">Week {{ $week }}</div>
                                                <div class="divide-y divide-gray-100">
                                                    @foreach ($items as $item)
                                                        <div class="px-4 py-3">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div>
                                                                    <div class="font-semibold text-gray-900">{{ $item->title }}</div>
                                                                    <div class="text-xs text-gray-500">{{ str_replace('_', ' ', ucfirst($item->status)) }}</div>
                                                                </div>
                                                                @if ($item->status === 'completed')
                                                                    <span class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">Completed</span>
                                                                @elseif ($item->isBehindSchedule())
                                                                    <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 text-xs font-semibold">Behind</span>
                                                                @endif
                                                            </div>
                                                            @if ($item->teacher_comment)
                                                                <p class="text-sm text-gray-500 mt-2">Teacher note: {{ $item->teacher_comment }}</p>
                                                            @endif
                                                            @if ($item->subtopics->count())
                                                                <div class="mt-3 flex flex-wrap gap-2">
                                                                    @foreach ($item->subtopics as $subtopic)
                                                                        <span class="px-2 py-1 rounded-full border text-xs font-semibold {{ $subtopic->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                                                            {{ $subtopic->status === 'completed' ? '✓' : '○' }} {{ $subtopic->title }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900">HOD Review</h3>
                        @if ($scheme->review_comment)
                            <div class="mt-3 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">{{ $scheme->review_comment }}</div>
                        @endif

                        <form method="POST" action="{{ route($schemeRoutes['approve'], $scheme) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')
                            <textarea name="review_comment" rows="3" placeholder="Optional approval comment" class="w-full rounded-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm"></textarea>
                            <button class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Approve Scheme</button>
                        </form>

                        <form method="POST" action="{{ route($schemeRoutes['requestChanges'], $scheme) }}" class="mt-4 space-y-3">
                            @csrf
                            @method('PATCH')
                            <textarea name="review_comment" rows="3" required placeholder="Explain what must be corrected" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm"></textarea>
                            <button class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700">Request Changes</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900">Recent Updates</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($scheme->logs->take(8) as $log)
                                <div class="text-sm border-b border-gray-100 pb-3 last:border-0">
                                    <div class="font-semibold text-gray-800">{{ str_replace('_', ' ', ucfirst($log->action)) }}</div>
                                    <div class="text-gray-500">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</div>
                                    @if ($log->new_status)
                                        <div class="text-xs text-gray-500">{{ $log->old_status }} → {{ $log->new_status }}</div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No progress updates yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
