<x-app-layout>
    @php
        $summaryRoutePrefix =
            $summaryRoutePrefix ??
            (request()->routeIs('accounts-officer.*')
                ? 'accounts-officer'
                : (request()->routeIs('office.*')
                    ? 'office'
                    : 'admin'));
        $summaryDashboardRoute = match ($summaryRoutePrefix) {
            'accounts-officer' => 'accounts-officer.dashboard',
            'office' => 'office.dashboard',
            default => 'admin.dashboard',
        };
        $hasSummary = !empty($summary);
        $summaryQuery = [
            'academic_year_id' => $selectedAcademicYearId,
            'class_id' => $selectedClassId,
            'term_id' => $selectedTermId,
            'exam_type' => $selectedExamType,
        ];
    @endphp

    <x-slot name="header">
        <div class="mt-16 p-6 bg-slate-800 rounded-2xl shadow-sm border border-slate-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-white">Exam Summary Sheets</h2>
                    <p class="text-slate-400 text-sm mt-1">
                        Generate, preview and download class summary sheets.
                    </p>
                </div>
                <a href="{{ route($summaryDashboardRoute) }}"
                    class="text-sm font-semibold text-white hover:text-slate-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white rounded">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 shadow-sm">
                    <div class="font-semibold mb-2">Please correct the following:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-sm font-semibold uppercase tracking-widest text-slate-400">Filters</h3>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route($summaryRoutePrefix . '.exam-summaries.index') }}"
                        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                        <div>
                            <label for="academic_year_id" class="block text-sm font-medium text-slate-700 mb-1">
                                Academic Year
                            </label>
                            <select name="academic_year_id" id="academic_year_id"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select academic year</option>
                                @foreach ($academicYears as $academicYear)
                                    <option value="{{ $academicYear->id }}"
                                        @selected((string) $selectedAcademicYearId === (string) $academicYear->id)>
                                        {{ $academicYear->year_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="class_id" class="block text-sm font-medium text-slate-700 mb-1">
                                Class
                            </label>
                            <select name="class_id" id="class_id"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}"
                                        @selected((string) $selectedClassId === (string) $class->id)>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="term_id" class="block text-sm font-medium text-slate-700 mb-1">
                                Term
                            </label>
                            <select name="term_id" id="term_id"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select term</option>
                                @foreach ($terms as $term)
                                    <option value="{{ $term->id }}" @selected((string) $selectedTermId === (string) $term->id)>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="exam_type" class="block text-sm font-medium text-slate-700 mb-1">
                                Exam Type
                            </label>
                            <select name="exam_type" id="exam_type"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="midterm" @selected($selectedExamType === 'midterm')>Midterm</option>
                                <option value="endterm" @selected($selectedExamType === 'endterm')>Endterm</option>
                            </select>
                        </div>

                        <div class="md:col-span-2 xl:col-span-4 flex flex-wrap gap-3 pt-2">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm">
                                Load Summary
                            </button>

                            @if ($hasSummary)
                                <a href="{{ route($summaryRoutePrefix . '.exam-summaries.preview', $summaryQuery) }}"
                                    target="_blank"
                                    class="inline-flex items-center px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white rounded-lg shadow-sm">
                                    Open Preview
                                </a>

                                <a href="{{ route($summaryRoutePrefix . '.exam-summaries.pdf', $summaryQuery) }}"
                                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm">
                                    Download PDF
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            @if ($hasSummary)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                {{ $summary['class']->name }} - {{ ucfirst($summary['exam_type']) }} Summary
                            </h3>
                            <p class="text-sm text-slate-600">
                                Teacher: {{ $summary['class_teacher_name'] }} |
                                Class Average:
                                {{ $summary['class_average'] !== null ? (int) ceil($summary['class_average']) : '-' }}
                            </p>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="min-w-full text-xs border-collapse">
                                <thead class="bg-slate-900 text-white">
                                    <tr>
                                        <th class="px-2 py-2 border border-slate-300 text-center">#</th>
                                        <th class="px-2 py-2 border border-slate-300 text-left">Surname</th>
                                        <th class="px-2 py-2 border border-slate-300 text-left">Name</th>
                                        <th class="px-2 py-2 border border-slate-300 text-center">Cls</th>

                                        @foreach ($summary['subjects'] as $subject)
                                            <th class="px-2 py-2 border border-slate-300 text-center whitespace-nowrap">
                                                {{ $subject['code'] }}
                                            </th>
                                        @endforeach

                                        <th class="px-2 py-2 border border-slate-300 text-center">AV</th>
                                        <th class="px-2 py-2 border border-slate-300 text-center">TOT</th>
                                        <th class="px-2 py-2 border border-slate-300 text-center">PS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($summary['rows'] as $i => $row)
                                        @php
                                            $parts = preg_split('/\s+/', trim($row['student_name'] ?? ''), 2);
                                            $surname = $parts[0] ?? '';
                                            $name = $parts[1] ?? '';
                                            $rowAverage = $row['average'] !== null ? (int) ceil($row['average']) : '';
                                            $rowTotal = $row['total'] !== null ? (int) ceil($row['total']) : '';
                                        @endphp
                                        <tr class="{{ $i % 2 ? 'bg-slate-50' : 'bg-white' }}">
                                            <td class="px-2 py-2 border border-slate-200 text-center">{{ $i + 1 }}</td>
                                            <td class="px-2 py-2 border border-slate-200 text-left whitespace-nowrap">{{ $surname }}</td>
                                            <td class="px-2 py-2 border border-slate-200 text-left whitespace-nowrap">{{ $name }}</td>
                                            <td class="px-2 py-2 border border-slate-200 text-center">{{ $summary['class']->name }}</td>

                                            @foreach ($summary['subjects'] as $subject)
                                                <td class="px-2 py-2 border border-slate-200 text-center whitespace-nowrap">
                                                    {{ $row['scores'][$subject['id']]['display'] ?? '' }}
                                                </td>
                                            @endforeach

                                            <td class="px-2 py-2 border border-slate-200 text-center">{{ $rowAverage }}</td>
                                            <td class="px-2 py-2 border border-slate-200 text-center">{{ $rowTotal }}</td>
                                            <td class="px-2 py-2 border border-slate-200 text-center">{{ $row['position'] ?? '' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 7 + count($summary['subjects']) }}" class="px-2 py-8 border border-slate-200 text-center text-slate-500">
                                                No marks found for the selected class, term and exam type.
                                            </td>
                                        </tr>
                                    @endforelse

                                    <tr class="bg-amber-50 font-semibold">
                                        <td colspan="4" class="px-2 py-2 border border-slate-200 text-center">Average</td>

                                        @foreach ($summary['subjects'] as $subject)
                                            @php
                                                $avg = collect($summary['subject_averages'] ?? [])->firstWhere('subject_id', $subject['id']);
                                                $avgDisplay = !empty($avg['average']) ? ((int) ceil($avg['average'])) . ($avg['grade'] ?? '') : '';
                                            @endphp
                                            <td class="px-2 py-2 border border-slate-200 text-center whitespace-nowrap">
                                                {{ $avgDisplay }}
                                            </td>
                                        @endforeach

                                        <td class="px-2 py-2 border border-slate-200 text-center">
                                            {{ $summary['class_average'] !== null ? (int) ceil($summary['class_average']) : '' }}
                                        </td>
                                        <td class="px-2 py-2 border border-slate-200"></td>
                                        <td class="px-2 py-2 border border-slate-200"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 text-sm text-slate-600">
                            <strong>Grade Key:</strong>
                            @foreach ($summary['grade_scale'] as $grade => $range)
                                <span class="mr-4"><strong>{{ $grade }}</strong>: {{ $range }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-4 border-b border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-900">PDF Preview</h3>
                        <p class="text-sm text-slate-600">
                            This is the actual generated PDF rendered in the browser.
                        </p>
                    </div>
                    <div class="p-4">
                        <iframe src="{{ route($summaryRoutePrefix . '.exam-summaries.preview', $summaryQuery) }}"
                            class="w-full rounded-lg border border-slate-300" style="height: 900px;"></iframe>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <p class="text-sm text-slate-600">
                        Select an academic year, class, term and exam type to load a summary sheet.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
