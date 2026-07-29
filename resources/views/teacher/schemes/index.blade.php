<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-2xl text-white leading-tight">Schemes of Work</h2>
                    <p class="text-white/80 text-sm mt-1">Plan syllabus coverage and update teaching progress.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if ($isHod)
                        <a href="{{ route('teacher.hod.schemes.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white/15 hover:bg-white/25 rounded-lg text-white text-sm font-semibold transition">
                            HOD Dashboard
                        </a>
                    @endif
                    <a href="{{ route('teacher.schemes.create') }}" class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 rounded-lg text-sm font-semibold shadow-sm hover:bg-emerald-50 transition">
                        New Scheme
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 kw-soft-section min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Active Academic Year</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ $academicYear?->year_name ?? 'N/A' }}</h3>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">My Schemes</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-2">{{ $schemes->count() }}</h3>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <p class="text-sm text-gray-500">Average Completion</p>
                    <h3 class="text-2xl font-bold text-indigo-600 mt-2">{{ $schemes->count() ? round($schemes->avg(fn($s) => $s->completionPct()), 1) : 0 }}%</h3>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">My Scheme Progress</h3>
                        <p class="text-sm text-gray-500">Each scheme is linked to one teaching assignment.</p>
                    </div>
                </div>

                @if ($schemes->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        <p>No schemes have been created yet.</p>
                        <a href="{{ route('teacher.schemes.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">Create your first scheme</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Scheme</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Progress</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pacing</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach ($schemes as $scheme)
                                    @php
                                        $pct = $scheme->completionPct();
                                        $expected = $scheme->expectedPct();
                                        $pacing = $scheme->pacingStatus();
                                        $pacingClasses = [
                                            'ahead' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'on_track' => 'bg-green-50 text-green-700 border-green-200',
                                            'behind' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
                                            'critical' => 'bg-red-50 text-red-700 border-red-200',
                                            'no_plan' => 'bg-gray-50 text-gray-600 border-gray-200',
                                        ];
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-900">{{ $scheme->title }}</div>
                                            <div class="text-sm text-gray-500">{{ $scheme->academicYear?->year_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <div>{{ $scheme->teacherSubject?->class?->name ?? 'N/A' }}</div>
                                            <div class="text-gray-500">{{ $scheme->teacherSubject?->subject?->name ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4 min-w-[220px]">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-2 bg-emerald-500 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-700">{{ $pct }}%</span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">Expected: {{ $expected }}%</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold border {{ $pacingClasses[$pacing] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                                                {{ str_replace('_', ' ', ucfirst($pacing)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                                                {{ str_replace('_', ' ', ucfirst($scheme->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('teacher.schemes.show', $scheme) }}" class="text-emerald-700 hover:text-emerald-900 font-semibold text-sm">Open</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
