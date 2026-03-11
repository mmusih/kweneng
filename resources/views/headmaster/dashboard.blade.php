<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-4 bg-gradient-to-r from-slate-700 to-slate-900 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Headmaster Dashboard
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- 1. CURRENT ACADEMIC STATUS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Academic Status</h3>
                        <p class="text-sm text-gray-500 mt-1">Current year, current term, and reporting readiness.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        <div class="border rounded-lg p-5 bg-gradient-to-br from-blue-50 to-white shadow-sm">
                            <p class="text-sm text-gray-500">Academic Year</p>
                            <p class="text-2xl font-bold text-blue-600 mt-2">
                                {{ $activeAcademicYear?->year_name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ $activeAcademicYear ? ucfirst($activeAcademicYear->status) : 'No active year' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gradient-to-br from-indigo-50 to-white shadow-sm">
                            <p class="text-sm text-gray-500">Current Term</p>
                            <p class="text-2xl font-bold text-indigo-600 mt-2">
                                {{ $currentTerm?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ $currentTerm ? ucfirst($currentTerm->status) : 'No active term' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gradient-to-br from-green-50 to-white shadow-sm">
                            <p class="text-sm text-gray-500">Marks Completion</p>
                            <p class="text-2xl font-bold text-green-600 mt-2">
                                {{ $dashboard['averageMarksCompletion'] !== null ? number_format($dashboard['averageMarksCompletion'], 1) . '%' : 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ $dashboard['classesFullySubmitted'] }} classes fully submitted
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gradient-to-br from-purple-50 to-white shadow-sm">
                            <p class="text-sm text-gray-500">Headmaster Comments</p>
                            <p class="text-2xl font-bold text-purple-600 mt-2">
                                {{ $dashboard['studentsWithComments'] }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ $dashboard['studentsWithoutComments'] }} pending
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. ACADEMIC ANALYTICS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Academic Analytics</h3>
                        <p class="text-sm text-gray-500 mt-1">School-wide academic performance snapshot.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">School Average</p>
                            <p class="text-2xl font-bold text-gray-900 mt-2">
                                {{ $dashboard['schoolAverage'] !== null ? number_format($dashboard['schoolAverage'], 2) . '%' : 'N/A' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-blue-50">
                            <p class="text-sm text-gray-500">Midterm Average</p>
                            <p class="text-2xl font-bold text-blue-600 mt-2">
                                {{ $dashboard['midtermAverage'] !== null ? number_format($dashboard['midtermAverage'], 2) . '%' : 'N/A' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-green-50">
                            <p class="text-sm text-gray-500">Endterm Average</p>
                            <p class="text-2xl font-bold text-green-600 mt-2">
                                {{ $dashboard['endtermAverage'] !== null ? number_format($dashboard['endtermAverage'], 2) . '%' : 'N/A' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-yellow-50">
                            <p class="text-sm text-gray-500">At-Risk Students</p>
                            <p class="text-2xl font-bold text-yellow-600 mt-2">
                                {{ $dashboard['atRiskStudentsCount'] }}
                            </p>
                            <p class="text-sm text-gray-500 mt-2">Average below 50%</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Best Class</p>
                            <p class="text-lg font-bold text-green-600 mt-2">
                                {{ $dashboard['bestClass']?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $dashboard['bestClass'] && $dashboard['bestClass']->average_score !== null ? number_format($dashboard['bestClass']->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Weakest Class</p>
                            <p class="text-lg font-bold text-red-600 mt-2">
                                {{ $dashboard['weakestClass']?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $dashboard['weakestClass'] && $dashboard['weakestClass']->average_score !== null ? number_format($dashboard['weakestClass']->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Top Subject</p>
                            <p class="text-lg font-bold text-blue-600 mt-2">
                                {{ $dashboard['topSubject']?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $dashboard['topSubject'] && $dashboard['topSubject']->average_score !== null ? number_format($dashboard['topSubject']->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-gray-50">
                            <p class="text-sm text-gray-500">Weakest Subject</p>
                            <p class="text-lg font-bold text-red-600 mt-2">
                                {{ $dashboard['weakestSubject']?->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $dashboard['weakestSubject'] && $dashboard['weakestSubject']->average_score !== null ? number_format($dashboard['weakestSubject']->average_score, 2) . '%' : 'No data' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. SCHOOL OPERATIONS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">School Operations</h3>
                        <p class="text-sm text-gray-500 mt-1">Attendance, punctuality, behaviour, and workflow
                            indicators.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                        <div class="border rounded-lg p-5 bg-indigo-50">
                            <p class="text-sm text-gray-500">Attendance Rate</p>
                            <p class="text-2xl font-bold text-indigo-600 mt-2">
                                {{ $dashboard['attendanceRate'] !== null ? number_format($dashboard['attendanceRate'], 1) . '%' : 'N/A' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-green-50">
                            <p class="text-sm text-gray-500">On-Time Rate</p>
                            <p class="text-2xl font-bold text-green-600 mt-2">
                                {{ $dashboard['punctualityOnTimeRate'] !== null ? number_format($dashboard['punctualityOnTimeRate'], 1) . '%' : 'N/A' }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-orange-50">
                            <p class="text-sm text-gray-500">Behaviour Incidents</p>
                            <p class="text-2xl font-bold text-orange-600 mt-2">
                                {{ $dashboard['behaviourIncidentCount'] }}
                            </p>
                        </div>

                        <div class="border rounded-lg p-5 bg-red-50">
                            <p class="text-sm text-gray-500">Major Incidents</p>
                            <p class="text-2xl font-bold text-red-600 mt-2">
                                {{ $dashboard['majorBehaviourCount'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. STUDENTS NEEDING ATTENTION --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Students Requiring Attention</h3>
                        <p class="text-sm text-gray-500 mt-1">Lowest-performing students based on current academic data.
                        </p>
                    </div>

                    @if ($dashboard['recentAtRiskStudents']->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            @foreach ($dashboard['recentAtRiskStudents'] as $student)
                                <div class="border rounded-lg p-4 bg-red-50">
                                    <p class="font-semibold text-gray-900">
                                        {{ $student->user->name ?? 'Unknown Student' }}</p>
                                    <p class="text-sm text-gray-600 mt-1">{{ $student->admission_no ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ $student->currentClass->name ?? 'No Class' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg bg-green-50 p-4 text-green-800">
                            No at-risk students identified from the current academic data.
                        </div>
                    @endif
                </div>
            </div>

            {{-- 5. QUICK ACTIONS --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 border-b border-gray-200 pb-3">
                        <h3 class="text-xl font-semibold text-gray-800">Quick Actions</h3>
                        <p class="text-sm text-gray-500 mt-1">Fast access to review, reporting, and oversight tools.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <a href="{{ route('headmaster.comments.index') }}"
                            class="border rounded-lg p-5 hover:shadow-md transition hover:border-indigo-300">
                            <h4 class="font-semibold text-gray-900">Headmaster Comments</h4>
                            <p class="text-sm text-gray-500 mt-1">Review and add comments for report cards.</p>
                        </a>

                        <a href="{{ route('headmaster.reports.index') }}"
                            class="border rounded-lg p-5 hover:shadow-md transition hover:border-green-300">
                            <h4 class="font-semibold text-gray-900">Reports</h4>
                            <p class="text-sm text-gray-500 mt-1">Open report cards and student report views.</p>
                        </a>

                        <a href="{{ route('headmaster.exam-summaries.index') }}"
                            class="border rounded-lg p-5 hover:shadow-md transition hover:border-blue-300">
                            <h4 class="font-semibold text-gray-900">Exam Summaries</h4>
                            <p class="text-sm text-gray-500 mt-1">View class exam summary sheets and exports.</p>
                        </a>

                        <a href="{{ route('headmaster.marks.index') }}"
                            class="border rounded-lg p-5 hover:shadow-md transition hover:border-yellow-300">
                            <h4 class="font-semibold text-gray-900">Marks Monitor</h4>
                            <p class="text-sm text-gray-500 mt-1">Track marks entry, completeness, and updates.</p>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
