<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-indigo-600 to-blue-700 rounded-lg shadow-lg">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Exam Summary Sheets
            </h2>
            <p class="text-blue-100 text-sm mt-1">
                Generate class result sheets for meetings and analysis
            </p>
        </div>
    </x-slot>

    <style>
        @media print {

            .text-red-700,
            .text-green-700 {
                color: black !important;
            }

            .bg-red-50,
            .bg-green-50,
            .bg-blue-50,
            .bg-gray-50,
            .hover\:bg-gray-50:hover {
                background: white !important;
            }

            .font-semibold {
                font-weight: normal !important;
            }

            .shadow-sm,
            .shadow-lg {
                box-shadow: none !important;
            }
        }
    </style>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="GET" action="{{ route('admin.exam-summaries.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                            <select name="academic_year_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                                <option value="">Select academic year</option>
                                @foreach ($academicYears as $year)
                                    <option value="{{ $year->id }}"
                                        {{ (string) $selectedAcademicYearId === (string) $year->id ? 'selected' : '' }}>
                                        {{ $year->year_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Class</label>
                            <select name="class_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Term</label>
                            <select name="term_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                                <option value="">Select term</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}"
                                        {{ (string) $selectedTermId === (string) $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Exam Type</label>
                            <select name="exam_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                                <option value="midterm" {{ $selectedExamType === 'midterm' ? 'selected' : '' }}>Midterm
                                </option>
                                <option value="endterm" {{ $selectedExamType === 'endterm' ? 'selected' : '' }}>Endterm
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md">
                            Generate Summary Sheet
                        </button>
                    </div>
                </form>
            </div>

            @if ($summary)
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $summary['class']->name }} - {{ ucfirst($summary['exam_type']) }} Summary Sheet
                            </h3>
                            <p class="text-sm text-gray-500">
                                Subject marks, totals, averages, and positions
                            </p>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="text-sm text-gray-700 md:text-right">
                                <p><span class="font-semibold">Class Teacher:</span>
                                    {{ $summary['class_teacher_name'] }}</p>
                                <p><span class="font-semibold">Class Average:</span>
                                    {{ $summary['class_average'] !== null ? number_format($summary['class_average'], 2) : 'N/A' }}
                                </p>
                            </div>

                            <a href="{{ route('admin.exam-summaries.pdf', [
                                'academic_year_id' => $selectedAcademicYearId,
                                'class_id' => $selectedClassId,
                                'term_id' => $selectedTermId,
                                'exam_type' => $selectedExamType,
                            ]) }}"
                                class="bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-md">
                                Export PDF
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-300 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 px-3 py-2 text-left">Student Name</th>
                                    @foreach ($summary['subjects'] as $subject)
                                        <th class="border border-gray-300 px-3 py-2 text-center">{{ $subject['code'] }}
                                        </th>
                                    @endforeach
                                    <th class="border border-gray-300 px-3 py-2 text-center">TOT</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center">AVG</th>
                                    <th class="border border-gray-300 px-3 py-2 text-center">POS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary['rows'] as $row)
                                    <tr
                                        class="
                                        @if ($row['average'] !== null) {{ $row['average'] < 60 ? 'bg-red-50' : 'bg-green-50' }} @endif
                                        hover:bg-gray-50 print:bg-white
                                    ">
                                        <td class="border border-gray-300 px-3 py-2">{{ $row['student_name'] }}</td>

                                        @foreach ($summary['subjects'] as $subject)
                                            @php
                                                $score = $row['scores'][$subject['id']]['score'] ?? null;
                                            @endphp
                                            <td
                                                class="border border-gray-300 px-3 py-2 text-center
                                                @if ($score !== null) {{ $score < 60 ? 'text-red-700 font-semibold' : 'text-green-700 font-semibold' }} @endif
                                                print:text-black print:font-normal
                                            ">
                                                {{ $row['scores'][$subject['id']]['display'] ?? '' }}
                                            </td>
                                        @endforeach

                                        <td
                                            class="border border-gray-300 px-3 py-2 text-center font-medium print:font-normal">
                                            {{ $row['total'] !== null ? number_format($row['total'], 2) : '' }}
                                        </td>

                                        <td
                                            class="border border-gray-300 px-3 py-2 text-center
                                            @if ($row['average'] !== null) {{ $row['average'] < 60 ? 'text-red-700 font-semibold' : 'text-green-700 font-semibold' }} @endif
                                            print:text-black print:font-normal
                                        ">
                                            @if ($row['average'] !== null)
                                                {{ number_format($row['average'], 2) }}{{ $row['grade'] ?? '' }}
                                            @endif
                                        </td>

                                        <td
                                            class="border border-gray-300 px-3 py-2 text-center font-semibold print:font-normal">
                                            {{ $row['position'] ?? '' }}
                                        </td>
                                    </tr>
                                @endforeach

                                <tr class="bg-blue-50 font-semibold print:bg-white print:font-normal">
                                    <td class="border border-gray-300 px-3 py-2">Subject Avg</td>
                                    @foreach ($summary['subject_averages'] as $subjectAverage)
                                        @php
                                            $avg = $subjectAverage['average'];
                                        @endphp
                                        <td
                                            class="border border-gray-300 px-3 py-2 text-center
                                            @if ($avg !== null) {{ $avg < 60 ? 'text-red-700 font-semibold' : 'text-green-700 font-semibold' }} @endif
                                            print:text-black print:font-normal
                                        ">
                                            {{ $subjectAverage['display'] }}
                                        </td>
                                    @endforeach
                                    <td class="border border-gray-300 px-3 py-2 text-center"></td>
                                    <td
                                        class="border border-gray-300 px-3 py-2 text-center
                                        @if ($summary['class_average'] !== null) {{ $summary['class_average'] < 60 ? 'text-red-700 font-semibold' : 'text-green-700 font-semibold' }} @endif
                                        print:text-black print:font-normal
                                    ">
                                        {{ $summary['class_average'] !== null ? number_format($summary['class_average'], 2) : '' }}
                                    </td>
                                    <td class="border border-gray-300 px-3 py-2 text-center"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-lg bg-gray-50 border border-gray-200 p-4 print:bg-white">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2 print:font-normal">Grade Key</h4>
                        <div class="flex flex-wrap gap-4 text-sm text-gray-700">
                            @foreach ($summary['grade_scale'] as $grade => $range)
                                <span><span class="font-semibold print:font-normal">{{ $grade }}</span>:
                                    {{ $range }}</span>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-gray-500">
                        Blank cells indicate subjects not taken by that student or no recorded score for the selected
                        exam.
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
