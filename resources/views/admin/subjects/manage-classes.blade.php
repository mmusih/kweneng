<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Assign Subjects to Classes
            </h2>
        </div>
    </x-slot>

    @php
        $selectedAcademicYearId = old('academic_year_id', $selectedAcademicYearId ?? request('academic_year_id'));
        $selectedClassId = old('class_id', $selectedClassId ?? request('class_id'));
        $existingAssignments = $existingAssignments ?? collect();

        $subjectsByCore = $subjects
            ->sortBy([['is_core', 'desc'], ['display_order', 'asc'], ['name', 'asc']])
            ->groupBy(function ($subject) {
                return $subject->is_core ? 'Core Subjects' : 'Other Subjects';
            });
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Breadcrumb / Back Navigation -->
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                        <span class="mx-2">/</span>
                        <a href="{{ route('admin.subjects.index') }}" class="hover:text-indigo-600">Subjects</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-800 font-medium">Assign to Classes</span>
                    </div>

                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="text-2xl font-semibold">Bulk Subject-Class Assignment</h3>
                            <p class="text-gray-600 mt-1">
                                Select a class and academic year, then assign all subjects at once.
                            </p>
                        </div>

                        <a href="{{ route('admin.subjects.index') }}"
                            class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            ← Back to Subjects
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Step 1 -->
                    <div class="mb-8 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                        <h4 class="mb-4 text-lg font-semibold text-gray-800">Step 1: Select Academic Year and Class</h4>

                        <form method="GET" action="{{ route('admin.subjects.manage-classes') }}"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select Academic Year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ (string) $selectedAcademicYearId === (string) $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end">
                                <x-primary-button class="w-full justify-center">
                                    {{ __('Load Subjects') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    @if ($selectedAcademicYearId && $selectedClassId)
                        <!-- Step 2 -->
                        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-800">Step 2: Assign Subjects</h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Tick the required subjects and adjust marks if needed.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" id="select-all-subjects"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                            Select All
                                        </button>

                                        <button type="button" id="clear-all-subjects"
                                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                                            Clear All
                                        </button>

                                        <button type="button" id="select-core-subjects"
                                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                                            Select Core Only
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <input type="text" id="subject-search"
                                        class="block w-full sm:max-w-md rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Search subject by code or name...">

                                    <a href="{{ route('admin.subjects.manage-classes') }}"
                                        class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                        ← Change Class / Academic Year
                                    </a>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.subjects.bulk-save-classes') }}">
                                @csrf

                                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
                                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Assign
                                                </th>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Subject Code
                                                </th>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Subject Name
                                                </th>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Type
                                                </th>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Maximum Marks
                                                </th>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Passing Marks
                                                </th>
                                                <th
                                                    class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                    Remarks
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach ($subjectsByCore as $groupTitle => $groupSubjects)
                                                <tr class="bg-slate-50">
                                                    <td colspan="7"
                                                        class="px-4 py-3 text-sm font-semibold text-slate-700">
                                                        {{ $groupTitle }}
                                                    </td>
                                                </tr>

                                                @foreach ($groupSubjects as $subject)
                                                    @php
                                                        $assignment = $existingAssignments->get($subject->id);
                                                        $isChecked = old(
                                                            "subjects.{$subject->id}.selected",
                                                            $assignment ? 1 : 0,
                                                        );
                                                        $maxMarks = old(
                                                            "subjects.{$subject->id}.max_marks",
                                                            $assignment->max_marks ?? 100,
                                                        );
                                                        $passingMarks = old(
                                                            "subjects.{$subject->id}.passing_marks",
                                                            $assignment->passing_marks ?? 60,
                                                        );
                                                        $remarks = old(
                                                            "subjects.{$subject->id}.remarks",
                                                            $assignment->remarks ?? '',
                                                        );
                                                    @endphp

                                                    <tr class="subject-row transition-colors duration-150"
                                                        data-subject-text="{{ strtolower($subject->code . ' ' . $subject->name) }}">
                                                        <td class="px-4 py-4 align-middle">
                                                            <input type="checkbox"
                                                                name="subjects[{{ $subject->id }}][selected]"
                                                                value="1"
                                                                class="subject-checkbox h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                                data-is-core="{{ $subject->is_core ? '1' : '0' }}"
                                                                {{ $isChecked ? 'checked' : '' }}>
                                                        </td>

                                                        <td
                                                            class="px-4 py-4 align-middle text-sm font-semibold text-gray-900">
                                                            {{ $subject->code }}
                                                        </td>

                                                        <td class="px-4 py-4 align-middle text-sm text-gray-800">
                                                            {{ $subject->name }}
                                                        </td>

                                                        <td class="px-4 py-4 align-middle text-sm">
                                                            @if ($subject->is_core)
                                                                <span
                                                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                                                                    Core
                                                                </span>
                                                            @else
                                                                <span
                                                                    class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                                                    Optional
                                                                </span>
                                                            @endif
                                                        </td>

                                                        <td class="px-4 py-4 align-middle">
                                                            <input type="number"
                                                                name="subjects[{{ $subject->id }}][max_marks]"
                                                                step="0.01" min="0"
                                                                value="{{ $maxMarks }}"
                                                                class="block w-28 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        </td>

                                                        <td class="px-4 py-4 align-middle">
                                                            <input type="number"
                                                                name="subjects[{{ $subject->id }}][passing_marks]"
                                                                min="0" value="{{ $passingMarks }}"
                                                                class="block w-28 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        </td>

                                                        <td class="px-4 py-4 align-middle">
                                                            <input type="text"
                                                                name="subjects[{{ $subject->id }}][remarks]"
                                                                value="{{ $remarks }}"
                                                                placeholder="Optional remarks"
                                                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <a href="{{ route('admin.subjects.index') }}"
                                        class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                        ← Back to Subjects
                                    </a>

                                    <x-primary-button>
                                        {{ __('Save Subject Assignments') }}
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div
                            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-500">
                            Select an academic year and class first to manage subject assignments.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAllBtn = document.getElementById('select-all-subjects');
                const clearAllBtn = document.getElementById('clear-all-subjects');
                const selectCoreBtn = document.getElementById('select-core-subjects');
                const searchInput = document.getElementById('subject-search');

                const getCheckboxes = () => Array.from(document.querySelectorAll('.subject-checkbox'));

                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function() {
                        getCheckboxes().forEach(cb => cb.checked = true);
                    });
                }

                if (clearAllBtn) {
                    clearAllBtn.addEventListener('click', function() {
                        getCheckboxes().forEach(cb => cb.checked = false);
                    });
                }

                if (selectCoreBtn) {
                    selectCoreBtn.addEventListener('click', function() {
                        getCheckboxes().forEach(cb => {
                            cb.checked = cb.dataset.isCore === '1';
                        });
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const term = this.value.toLowerCase().trim();
                        const rows = document.querySelectorAll('.subject-row');

                        rows.forEach(row => {
                            const text = row.dataset.subjectText || '';
                            row.style.display = text.includes(term) ? '' : 'none';
                        });
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
