<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-4 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Exam Summaries
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('headmaster.exam-summaries.index') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                            <select name="academic_year_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Academic Year</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}"
                                        {{ $selectedAcademicYearId == $year->id ? 'selected' : '' }}>
                                        {{ $year->year_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                            <select name="class_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Term</label>
                            <select name="term_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Term</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}"
                                        {{ $selectedTermId == $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Exam Type</label>
                            <select name="exam_type" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="midterm" {{ $selectedExamType === 'midterm' ? 'selected' : '' }}>Midterm
                                </option>
                                <option value="endterm" {{ $selectedExamType === 'endterm' ? 'selected' : '' }}>Endterm
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-4 flex gap-3">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Generate Summary
                            </button>

                            @if ($summary)
                                <a href="{{ route('headmaster.exam-summaries.pdf', [
                                    'academic_year_id' => $selectedAcademicYearId,
                                    'class_id' => $selectedClassId,
                                    'term_id' => $selectedTermId,
                                    'exam_type' => $selectedExamType,
                                ]) }}"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                    Download PDF
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @if ($summary)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">
                                {{ $summary['class']->name }} - {{ ucfirst($summary['exam_type']) }} Summary
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Class Teacher: {{ $summary['class_teacher_name'] }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="border px-3 py-2 text-left">Student</th>
                                        @foreach ($summary['subjects'] as $subject)
                                            <th class="border px-3 py-2 text-center">{{ $subject['code'] }}</th>
                                        @endforeach
                                        <th class="border px-3 py-2 text-center">Total</th>
                                        <th class="border px-3 py-2 text-center">Average</th>
                                        <th class="border px-3 py-2 text-center">Grade</th>
                                        <th class="border px-3 py-2 text-center">Position</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($summary['rows'] as $row)
                                        <tr>
                                            <td class="border px-3 py-2">{{ $row['student_name'] }}</td>
                                            @foreach ($summary['subjects'] as $subject)
                                                <td class="border px-3 py-2 text-center">
                                                    {{ $row['scores'][$subject['id']]['display'] ?? '' }}
                                                </td>
                                            @endforeach
                                            <td class="border px-3 py-2 text-center">{{ $row['total'] }}</td>
                                            <td class="border px-3 py-2 text-center">{{ $row['average'] }}</td>
                                            <td class="border px-3 py-2 text-center">{{ $row['grade'] }}</td>
                                            <td class="border px-3 py-2 text-center">{{ $row['position'] }}</td>
                                        </tr>
                                    @endforeach

                                    <tr class="bg-gray-50 font-semibold">
                                        <td class="border px-3 py-2">Subject Avg</td>
                                        @foreach ($summary['subject_averages'] as $subjectAverage)
                                            <td class="border px-3 py-2 text-center">
                                                {{ $subjectAverage['display'] }}
                                            </td>
                                        @endforeach
                                        <td class="border px-3 py-2 text-center"></td>
                                        <td class="border px-3 py-2 text-center">{{ $summary['class_average'] }}</td>
                                        <td class="border px-3 py-2 text-center"></td>
                                        <td class="border px-3 py-2 text-center"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-sm text-gray-600">
                            <strong>Grade Scale:</strong>
                            @foreach ($summary['grade_scale'] as $grade => $range)
                                <span class="mr-4">{{ $grade }} = {{ $range }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
