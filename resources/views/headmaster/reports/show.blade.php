<x-app-layout>
    @php
        $reportRoutePrefix =
            $reportRoutePrefix ??
            (request()->routeIs('accounts-officer.*')
                ? 'accounts-officer'
                : (request()->routeIs('admin.*')
                    ? 'admin'
                    : (request()->routeIs('office.*')
                        ? 'office'
                        : 'headmaster')));
    @endphp
    <x-slot name="header">
        <div
            class="mt-16 p-6 kw-page-header rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Student Report Preview
                </h2>
                <p class="text-white/80 text-sm mt-1">
                    {{ $student->user->name }} · {{ $term->name }} · {{ $academicYear->year_name }}
                </p>
            </div>

            <a href="{{ route($reportRoutePrefix . '.reports.index', ['class_id' => $student->current_class_id, 'term_id' => $term->id]) }}"
                class="text-white hover:text-white/80 text-sm font-medium">
                Back to Reports
            </a>
            <a href="{{ route($reportRoutePrefix . '.reports.pdf', ['student' => $student->id, 'term_id' => $term->id]) }}"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-semibold">
                Download PDF
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white kw-panel p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Student Name</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $student->user->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Admission No</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $student->admission_no }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Class</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $student->currentClass->name ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Combined Average</p>
                        <p class="text-lg font-semibold text-indigo-600">
                            {{ $overallAverage !== null ? number_format($overallAverage, 2) . '%' : 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            @if (!empty($formFivePoints))
                <div class="rounded-2xl border-2 border-blue-600 bg-blue-50 p-6 shadow-sm dark:border-blue-400 dark:bg-blue-950/40">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-blue-800 dark:text-blue-300">
                                Form 5 Best-Six Points
                            </p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-brand-300">
                                English (EFL/ESL) and Mathematics (MaC/MaE) are compulsory, plus the best four other subjects.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            @foreach (['midterm' => 'Midterm', 'endterm' => 'Endterm'] as $examKey => $examLabel)
                                @php($points = $formFivePoints[$examKey])
                                <div class="min-w-40 rounded-xl border border-blue-200 bg-white px-5 py-3 text-center dark:border-blue-700 dark:bg-brand-800">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-brand-300">
                                        {{ $examLabel }} Points
                                    </p>
                                    <p class="mt-1 text-3xl font-black {{ $points['complete'] ? 'text-blue-700 dark:text-blue-300' : 'text-amber-600 dark:text-amber-300' }}">
                                        {{ $points['display'] }}
                                    </p>
                                    @if (!$points['complete'])
                                        <p class="mt-1 max-w-56 text-xs text-amber-700 dark:text-amber-300">
                                            Missing: {{ implode(', ', $points['missing']) }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white kw-panel p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Midterm Standing</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">Midterm Total:</span>
                            {{ $midtermTotal !== null ? number_format($midtermTotal, 2) : 'N/A' }}</p>
                        <p><span class="font-medium">Midterm Average:</span>
                            {{ $midtermAverage !== null ? number_format($midtermAverage, 2) . '%' : 'N/A' }}</p>
                        <p><span class="font-medium">Midterm Position:</span>
                            @if ($midtermRanking && $midtermRanking['position'] !== null)
                                {{ $midtermRanking['position'] }} / {{ $midtermRanking['class_size'] }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>

                <div class="bg-white kw-panel p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Endterm Standing</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">Endterm Total:</span>
                            {{ $endtermTotal !== null ? number_format($endtermTotal, 2) : 'N/A' }}</p>
                        <p><span class="font-medium">Endterm Average:</span>
                            {{ $endtermAverage !== null ? number_format($endtermAverage, 2) . '%' : 'N/A' }}</p>
                        <p><span class="font-medium">Endterm Position:</span>
                            @if ($endtermRanking && $endtermRanking['position'] !== null)
                                {{ $endtermRanking['position'] }} / {{ $endtermRanking['class_size'] }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white kw-panel p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Academic Performance</h3>
                </div>

                @if ($subjects->count())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Midterm
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endterm
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Average
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remark
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($subjects as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $row['subject_name'] }}
                                            @if ($row['subject_code'])
                                                <span class="text-gray-500">({{ $row['subject_code'] }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $row['midterm_score'] !== null ? number_format($row['midterm_score'], 2) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $row['endterm_score'] !== null ? number_format($row['endterm_score'], 2) : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $row['average'] !== null ? number_format($row['average'], 2) . '%' : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $row['grade'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $row['remarks'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500">No marks found for this term.</p>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white kw-panel p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Attendance</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">Rate:</span>
                            {{ $attendanceSummary['rate'] !== null ? number_format($attendanceSummary['rate'], 1) . '%' : 'N/A' }}
                        </p>
                        <p><span class="font-medium">Present:</span> {{ $attendanceSummary['present'] }}</p>
                        <p><span class="font-medium">Absent:</span> {{ $attendanceSummary['absent'] }}</p>
                        <p><span class="font-medium">Late:</span> {{ $attendanceSummary['late'] }}</p>
                        <p><span class="font-medium">Excused:</span> {{ $attendanceSummary['excused'] }}</p>
                    </div>
                </div>

                <div class="bg-white kw-panel p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Punctuality</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">On-Time Rate:</span>
                            {{ $punctualitySummary['on_time_rate'] !== null ? number_format($punctualitySummary['on_time_rate'], 1) . '%' : 'N/A' }}
                        </p>
                        <p><span class="font-medium">On Time:</span> {{ $punctualitySummary['on_time'] }}</p>
                        <p><span class="font-medium">Late:</span> {{ $punctualitySummary['late'] }}</p>
                        <p><span class="font-medium">Very Late:</span> {{ $punctualitySummary['very_late'] }}</p>
                        <p><span class="font-medium">Absent:</span> {{ $punctualitySummary['absent'] }}</p>
                    </div>
                </div>

                <div class="bg-white kw-panel p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Behaviour</h3>
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">Total:</span> {{ $behaviourSummary['total'] }}</p>
                        <p><span class="font-medium">Minor:</span> {{ $behaviourSummary['minor'] }}</p>
                        <p><span class="font-medium">Moderate:</span> {{ $behaviourSummary['moderate'] }}</p>
                        <p><span class="font-medium">Major:</span> {{ $behaviourSummary['major'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white kw-panel p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Headmaster Comment</h3>
                @if ($headmasterComment)
                    <p class="text-gray-800 leading-relaxed">{{ $headmasterComment->comment }}</p>
                @else
                    <p class="text-gray-500">No headmaster comment recorded yet.</p>
                @endif
            </div>

            <div class="bg-white kw-panel p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Behaviour Incidents</h3>
                @if ($behaviourSummary['recent']->count())
                    <div class="space-y-3">
                        @foreach ($behaviourSummary['recent'] as $record)
                            <div class="border rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ \Illuminate\Support\Carbon::parse($record->record_date)->format('d M Y') }}
                                            · {{ ucwords(str_replace('_', ' ', $record->category)) }}
                                        </p>
                                    </div>

                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if ($record->severity === 'major') bg-red-100 text-red-800
                                        @elseif($record->severity === 'moderate')
                                            bg-yellow-100 text-yellow-800
                                        @else
                                            bg-blue-100 text-blue-800 @endif">
                                        {{ ucfirst($record->severity) }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-700 mt-2">{{ $record->incident }}</p>

                                @if ($record->action_taken)
                                    <p class="text-sm text-gray-600 mt-2">
                                        <span class="font-medium">Action Taken:</span> {{ $record->action_taken }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">No behaviour incidents recorded for this term.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
