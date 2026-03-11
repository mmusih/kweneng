<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <h2 class="font-bold text-2xl text-center text-gray-800 leading-tight">
                Student Dashboard
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Welcome, {{ auth()->user()->name }}!</h3>
                        <p class="text-gray-600">Access your academic information and results here.</p>
                    </div>

                    @if (!$student)
                        <div class="bg-yellow-50 rounded-lg p-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Student Record Required</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Your student record could not be found. Please contact the school
                                            administration.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- TOP STATUS CARDS --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                            <div class="bg-blue-100 p-6 rounded-lg shadow">
                                <h4 class="text-lg font-semibold text-blue-800">Current Class</h4>
                                @if ($student->currentClass)
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ $student->currentClass->name }}
                                    </p>
                                    <p class="text-sm text-blue-700 mt-1">
                                        {{ $student->currentClass->academicYear->year_name ?? 'N/A' }}
                                    </p>
                                @else
                                    <p class="text-lg text-blue-600">Not Assigned</p>
                                @endif
                            </div>

                            <div class="bg-green-100 p-6 rounded-lg shadow">
                                <h4 class="text-lg font-semibold text-green-800">Academic Year</h4>
                                @if ($currentAcademicYear)
                                    <p class="text-2xl font-bold text-green-600">
                                        {{ $currentAcademicYear->year_name }}
                                    </p>
                                    @if ($currentAcademicYear->active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-800 mt-1">
                                            Active
                                        </span>
                                    @endif
                                @else
                                    <p class="text-lg text-green-600">Not Set</p>
                                @endif
                            </div>

                            <div class="bg-purple-100 p-6 rounded-lg shadow">
                                <h4 class="text-lg font-semibold text-purple-800">Current Term</h4>
                                @if ($currentTerm)
                                    <p class="text-2xl font-bold text-purple-600">
                                        {{ $currentTerm->name }}
                                    </p>
                                    <p class="text-sm text-purple-700 mt-1">
                                        {{ $currentTerm->start_date->format('M j') }} -
                                        {{ $currentTerm->end_date->format('M j, Y') }}
                                    </p>
                                    @if ($currentTerm->locked)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800 mt-1">
                                            Locked
                                        </span>
                                    @endif
                                @else
                                    <p class="text-lg text-purple-600">No Active Term</p>
                                @endif
                            </div>

                            <div class="bg-amber-100 p-6 rounded-lg shadow">
                                <h4 class="text-lg font-semibold text-amber-800">Subjects Assigned</h4>
                                <p class="text-2xl font-bold text-amber-600">
                                    {{ $stats['subjectsAssigned'] ?? 0 }}
                                </p>
                                <p class="text-sm text-amber-700 mt-1">
                                    {{ $stats['subjectsWithMarks'] ?? 0 }} with marks entered
                                </p>
                            </div>
                        </div>

                        {{-- PERFORMANCE SUMMARY --}}
                        <div class="mt-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Performance Summary</h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                                <div
                                    class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-100">
                                    <div class="flex items-center">
                                        <div class="rounded-full bg-blue-500 p-3">
                                            <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <h5 class="text-sm font-medium text-gray-900">Midterm Average</h5>
                                            <p class="text-2xl font-bold text-blue-600">
                                                {{ ($stats['midtermAverage'] ?? null) !== null ? number_format($stats['midtermAverage'], 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="bg-gradient-to-r from-green-50 to-emerald-50 p-6 rounded-lg border border-green-100">
                                    <div class="flex items-center">
                                        <div class="rounded-full bg-green-500 p-3">
                                            <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <h5 class="text-sm font-medium text-gray-900">Endterm Average</h5>
                                            <p class="text-2xl font-bold text-green-600">
                                                {{ ($stats['endtermAverage'] ?? null) !== null ? number_format($stats['endtermAverage'], 2) : 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h5 class="text-sm font-medium text-gray-700">Marks Entered</h5>
                                    <p class="text-2xl font-bold text-gray-900 mt-2">
                                        {{ $stats['subjectsWithMarks'] ?? 0 }}
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Current term subjects with recorded marks
                                    </p>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h5 class="text-sm font-medium text-gray-700">Results Access</h5>
                                    @if ($stats['feesBlocked'] ?? false)
                                        <p class="text-lg font-bold text-red-600 mt-2">Blocked</p>
                                        <p class="text-sm text-red-500 mt-1">Contact accounts office</p>
                                    @else
                                        <p class="text-lg font-bold text-green-600 mt-2">Allowed</p>
                                        <p class="text-sm text-green-500 mt-1">Access is active</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ACADEMIC STANDING --}}
                        @if ($performance)
                            <div class="mt-8">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">Academic Standing</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="bg-white border rounded-lg p-6 shadow-sm">
                                        <h5 class="text-sm font-medium text-gray-600">Performance Status</h5>
                                        <p class="mt-2 text-2xl font-bold text-indigo-600">
                                            {{ $performance['performance_label'] ?? 'N/A' }}
                                        </p>
                                    </div>

                                    <div class="bg-white border rounded-lg p-6 shadow-sm">
                                        <h5 class="text-sm font-medium text-gray-600">Endterm Position</h5>
                                        <p class="mt-2 text-2xl font-bold text-green-600">
                                            @if ($performance['endterm_position'] && $performance['endterm_position']['position'])
                                                {{ $performance['endterm_position']['position'] }}/{{ $performance['endterm_position']['class_size'] }}
                                            @else
                                                N/A
                                            @endif
                                        </p>
                                    </div>

                                    <div class="bg-white border rounded-lg p-6 shadow-sm">
                                        <h5 class="text-sm font-medium text-gray-600">Trend</h5>
                                        <p class="mt-2 text-2xl font-bold text-blue-600">
                                            {{ $performance['trend'] ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- LATEST MARKS SNAPSHOT --}}
                        <div class="mt-8">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-lg font-semibold text-gray-800">Latest Marks Snapshot</h4>
                                <a href="{{ route('student.marks.index') }}"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    View Detailed Marks →
                                </a>
                            </div>

                            @if ($currentTerm)
                                @if ($latestMarks->count() > 0)
                                    <div class="overflow-x-auto">
                                        <table
                                            class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Subject</th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Midterm</th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Endterm</th>
                                                    <th
                                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Teacher</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($latestMarks as $mark)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="font-medium text-gray-900">
                                                                {{ $mark->subject->name ?? 'Unknown Subject' }}</div>
                                                            <div class="text-sm text-gray-500">
                                                                {{ $mark->subject->code ?? 'N/A' }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $mark->midterm_score ?? 'N/A' }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $mark->endterm_score ?? 'N/A' }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $mark->teacher?->user?->name ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-lg p-6 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">No marks entered yet</h3>
                                        <p class="mt-1 text-sm text-gray-500">Your current term marks will appear here
                                            once teachers submit them.</p>
                                    </div>
                                @endif
                            @else
                                <div class="bg-blue-50 rounded-lg p-6">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-blue-800">Marks Information</h3>
                                            <div class="mt-2 text-sm text-blue-700">
                                                <p>There is no active term currently. Marks will be displayed here once
                                                    a term is active and marks are entered.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- STUDENT LIFE SUMMARY --}}
                        @if ($attendanceSummary || $punctualitySummary || $behaviourSummary)
                            <div class="mt-8">
                                <h4 class="text-lg font-semibold text-gray-800 mb-4">Student Life Summary</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    @if ($attendanceSummary)
                                        <div class="bg-white border rounded-lg p-6 shadow-sm">
                                            <h5 class="text-lg font-semibold text-gray-700 mb-3">Attendance</h5>

                                            <div class="space-y-1 text-sm">
                                                <div class="flex justify-between">
                                                    <span>Present</span>
                                                    <span
                                                        class="font-semibold text-green-600">{{ $attendanceSummary['present'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Absent</span>
                                                    <span
                                                        class="font-semibold text-red-600">{{ $attendanceSummary['absent'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Late</span>
                                                    <span
                                                        class="font-semibold text-yellow-600">{{ $attendanceSummary['late'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Excused</span>
                                                    <span
                                                        class="font-semibold text-gray-600">{{ $attendanceSummary['excused'] }}</span>
                                                </div>
                                            </div>

                                            @if ($attendanceSummary['rate'] !== null)
                                                <div class="mt-3 text-sm">
                                                    Attendance Rate:
                                                    <span
                                                        class="font-bold text-indigo-600">{{ $attendanceSummary['rate'] }}%</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($punctualitySummary)
                                        <div class="bg-white border rounded-lg p-6 shadow-sm">
                                            <h5 class="text-lg font-semibold text-gray-700 mb-3">Punctuality</h5>

                                            <div class="space-y-1 text-sm">
                                                <div class="flex justify-between">
                                                    <span>On Time</span>
                                                    <span
                                                        class="font-semibold text-green-600">{{ $punctualitySummary['on_time'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Late</span>
                                                    <span
                                                        class="font-semibold text-yellow-600">{{ $punctualitySummary['late'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Very Late</span>
                                                    <span
                                                        class="font-semibold text-red-600">{{ $punctualitySummary['very_late'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Absent</span>
                                                    <span
                                                        class="font-semibold text-gray-600">{{ $punctualitySummary['absent'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if ($behaviourSummary)
                                        <div class="bg-white border rounded-lg p-6 shadow-sm">
                                            <h5 class="text-lg font-semibold text-gray-700 mb-3">Behaviour</h5>

                                            <div class="space-y-1 text-sm">
                                                <div class="flex justify-between">
                                                    <span>Total Records</span>
                                                    <span
                                                        class="font-semibold text-gray-900">{{ $behaviourSummary['total'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Minor</span>
                                                    <span
                                                        class="font-semibold text-yellow-600">{{ $behaviourSummary['minor'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Moderate</span>
                                                    <span
                                                        class="font-semibold text-orange-600">{{ $behaviourSummary['moderate'] }}</span>
                                                </div>

                                                <div class="flex justify-between">
                                                    <span>Major</span>
                                                    <span
                                                        class="font-semibold text-red-600">{{ $behaviourSummary['major'] }}</span>
                                                </div>
                                            </div>

                                            <div class="mt-3 text-sm">
                                                Status:
                                                <span
                                                    class="font-bold text-indigo-600">{{ $behaviourSummary['label'] }}</span>
                                            </div>

                                            @if ($behaviourSummary['latest'])
                                                <div class="mt-3 text-xs text-gray-500">
                                                    Latest: {{ ucfirst($behaviourSummary['latest']->category) }} -
                                                    {{ ucfirst($behaviourSummary['latest']->severity) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- QUICK INFORMATION --}}
                        <div class="mt-8">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Quick Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-medium text-gray-700">Access Status</h5>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <div
                                                class="w-3 h-3 rounded-full {{ $stats['feesBlocked'] ?? false ? 'bg-red-500' : 'bg-green-500' }} mr-2">
                                            </div>
                                            <span class="text-sm">
                                                {{ $stats['feesBlocked'] ?? false ? 'Results Access Blocked' : 'Results Access Enabled' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center">
                                            <div
                                                class="w-3 h-3 rounded-full {{ ($stats['subjectsAssigned'] ?? 0) > 0 ? 'bg-green-500' : 'bg-yellow-500' }} mr-2">
                                            </div>
                                            <span class="text-sm">
                                                {{ $stats['subjectsAssigned'] ?? 0 }} subject(s) assigned
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-medium text-gray-700">Academic Progress</h5>
                                    <p class="text-sm text-gray-600 mt-2">
                                        @if (($stats['subjectsWithMarks'] ?? 0) > 0)
                                            Marks have been entered for {{ $stats['subjectsWithMarks'] }} subject(s)
                                            this term.
                                        @elseif($currentTerm)
                                            No marks have been entered yet for the current term.
                                        @else
                                            Academic progress information will appear when a term is active.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
