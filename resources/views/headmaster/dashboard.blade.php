<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-linear-to-r from-blue-600 to-indigo-700 rounded-lg shadow-lg">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Headmaster Dashboard
            </h2>
            <p class="text-blue-100 text-sm mt-1">
                Academic oversight and school performance summary
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(!$activeAcademicYear)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active academic year found. Please ask the administrator to activate an academic year.
                </div>
            @else

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-500">Academic Year</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            {{ $activeAcademicYear->year_name }}
                        </h3>
                        <p class="text-sm mt-2 text-gray-600">
                            Status: {{ ucfirst($activeAcademicYear->status) }}
                        </p>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-500">Active Term</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            {{ $activeTerm?->name ?? 'No Active Term' }}
                        </h3>
                        <p class="text-sm mt-2 text-gray-600">
                            {{ $activeTerm ? ucfirst($activeTerm->status) : 'Please activate a term' }}
                        </p>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-500">School Average</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            {{ $schoolAverage !== null ? number_format($schoolAverage, 2) . '%' : 'N/A' }}
                        </h3>
                        <p class="text-sm mt-2 text-gray-600">
                            From {{ $totalMarks }} mark records
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-500">Headmaster Comments</p>
                        <h3 class="text-lg font-bold text-gray-900 mt-2">
                            Comment Center
                        </h3>

                        <a href="{{ route('headmaster.comments.index') }}"
                           class="mt-4 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-semibold">
                            Write Comments
                        </a>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-500">Marks Monitoring</p>
                        <h3 class="text-lg font-bold text-gray-900 mt-2">
                            Marks Completion
                        </h3>

                        <p class="text-sm text-gray-500 mt-2">
                            Monitor class-level and teacher-level marks entry progress.
                        </p>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <p class="text-sm text-gray-500">Student Performance</p>
                        <h3 class="text-lg font-bold text-gray-900 mt-2">
                            Top & At-Risk Students
                        </h3>

                        <p class="text-sm text-gray-500 mt-2">
                            Identify academic excellence and learners needing intervention.
                        </p>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Class Performance
                    </h3>

                    @if($classPerformance->count())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Average</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($classPerformance as $class)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $class->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $class->average_score !== null ? number_format($class->average_score, 2) . '%' : 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $class->marks_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No class performance data available.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Subject Performance
                    </h3>

                    @if($subjectPerformance->count())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Average</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($subjectPerformance as $subject)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $subject->name }}
                                                @if($subject->code)
                                                    <span class="text-gray-500">({{ $subject->code }})</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ $subject->average_score !== null ? number_format($subject->average_score, 2) . '%' : 'N/A' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $subject->marks_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500">No subject performance data available.</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">
                            Marks Entry Completion by Class
                        </h3>

                        @if($marksCompletion->count())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subjects</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks Entered</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($marksCompletion as $row)
                                            @php
                                                $completion = min($row['completion'], 100);

                                                if ($completion < 50) {
                                                    $barColor = 'bg-red-500';
                                                    $textColor = 'text-red-600';
                                                } elseif ($completion < 80) {
                                                    $barColor = 'bg-yellow-500';
                                                    $textColor = 'text-yellow-600';
                                                } else {
                                                    $barColor = 'bg-green-600';
                                                    $textColor = 'text-green-600';
                                                }
                                            @endphp

                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $row['class_name'] }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $row['students'] }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $row['subjects'] }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $row['actual_marks'] }} / {{ $row['expected_marks'] }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                                            <div
                                                                class="h-3 rounded-full {{ $barColor }}"
                                                                style="width: {{ $completion }}%;"
                                                            ></div>
                                                        </div>

                                                        <span class="font-semibold {{ $textColor }}">
                                                            {{ number_format($completion, 1) }}%
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500">No marks completion data available.</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">
                            Teacher Marks Entry Monitoring
                        </h3>

                        @if($teacherCompletion->count())
                            <div class="space-y-4">
                                @foreach($teacherCompletion as $teacher)
                                    @php
                                        $completion = min($teacher['completion'], 100);

                                        if ($completion < 50) {
                                            $barColor = 'bg-red-500';
                                            $textColor = 'text-red-600';
                                        } elseif ($completion < 80) {
                                            $barColor = 'bg-yellow-500';
                                            $textColor = 'text-yellow-600';
                                        } else {
                                            $barColor = 'bg-green-600';
                                            $textColor = 'text-green-600';
                                        }
                                    @endphp

                                    <div class="border rounded-lg p-4">
                                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h4 class="text-base font-semibold text-gray-900">
                                                        {{ $teacher['teacher_name'] }}
                                                    </h4>

                                                    <span class="px-2 py-1 text-xs rounded-full {{ $teacher['role'] === 'Headmaster' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                                        {{ $teacher['role'] }}
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                                    <div>
                                                        <p class="font-medium text-gray-700 mb-1">Assigned Classes</p>
                                                        @if($teacher['assigned_classes']->count())
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($teacher['assigned_classes'] as $className)
                                                                    <span class="px-2 py-1 rounded bg-gray-100 text-gray-800 text-xs">
                                                                        {{ $className }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <p class="text-gray-500">None assigned</p>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <p class="font-medium text-gray-700 mb-1">Classes Entered</p>
                                                        @if($teacher['classes_entered']->count())
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($teacher['classes_entered'] as $className)
                                                                    <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs">
                                                                        {{ $className }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <p class="text-gray-500">No marks entered yet</p>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <p class="font-medium text-gray-700 mb-1">Classes Not Entered</p>
                                                        @if($teacher['classes_not_entered']->count())
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($teacher['classes_not_entered'] as $className)
                                                                    <span class="px-2 py-1 rounded bg-red-100 text-red-800 text-xs">
                                                                        {{ $className }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <p class="text-green-600">All assigned classes entered</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="w-full lg:w-72">
                                                <p class="text-sm text-gray-500 mb-2">
                                                    Marks Entered: {{ $teacher['actual_marks'] }} / {{ $teacher['expected_marks'] }}
                                                </p>

                                                <div class="flex items-center space-x-3">
                                                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                                        <div
                                                            class="h-3 rounded-full {{ $barColor }}"
                                                            style="width: {{ $completion }}%;"
                                                        ></div>
                                                    </div>

                                                    <span class="font-semibold {{ $textColor }}">
                                                        {{ number_format($completion, 1) }}%
                                                    </span>
                                                </div>

                                                <p class="text-xs text-gray-500 mt-2">
                                                    Assigned subjects: {{ $teacher['assigned_subjects_count'] }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No teacher assignment data available.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Top Performing Students
                        </h3>

                        @if($topStudents->count())
                            <div class="space-y-3">
                                @foreach($topStudents as $student)
                                    <div class="flex items-center justify-between border rounded-lg p-3">
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                {{ $student->student_name }}
                                            </p>

                                            <p class="text-sm text-gray-500">
                                                {{ $student->admission_no }}
                                            </p>
                                        </div>

                                        <div class="text-right">
                                            <p class="font-semibold text-green-600">
                                                {{ number_format($student->average_score, 2) }}%
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">
                                No student performance data available.
                            </p>
                        @endif
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Students At Risk
                        </h3>

                        @if($atRiskStudents->count())
                            <div class="space-y-3">
                                @foreach($atRiskStudents as $student)
                                    <div class="flex items-center justify-between border rounded-lg p-3">
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                {{ $student->student_name }}
                                            </p>

                                            <p class="text-sm text-gray-500">
                                                {{ $student->admission_no }}
                                            </p>
                                        </div>

                                        <div class="text-right">
                                            <p class="font-semibold text-red-600">
                                                {{ number_format($student->average_score, 2) }}%
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">
                                No at-risk students found.
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>