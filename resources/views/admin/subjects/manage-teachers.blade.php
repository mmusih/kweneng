<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Assign Teachers to Subjects
            </h2>
        </div>
    </x-slot>

    @php
        $selectedAcademicYearId = old('academic_year_id', $selectedAcademicYearId ?? request('academic_year_id'));
        $selectedTeacherId = old('teacher_id', $selectedTeacherId ?? request('teacher_id'));
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                        <span class="mx-2">/</span>
                        <a href="{{ route('admin.subjects.index') }}" class="hover:text-indigo-600">Subjects</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-800 font-medium">Assign Teachers</span>
                    </div>

                    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="text-2xl font-semibold">Bulk Teacher Assignment</h3>
                            <p class="text-gray-600 mt-1">
                                Select one teacher and assign all their class-subject combinations for one academic year
                                at once.
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
                        <h4 class="mb-4 text-lg font-semibold text-gray-800">Step 1: Select Academic Year and Teacher
                        </h4>

                        <form method="GET" action="{{ route('admin.subjects.manage-teachers') }}"
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
                                <x-input-label for="teacher_id" :value="__('Teacher')" />
                                <select id="teacher_id" name="teacher_id"
                                    class="block mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select Teacher</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}"
                                            {{ (string) $selectedTeacherId === (string) $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end">
                                <x-primary-button class="w-full justify-center">
                                    {{ __('Load Assignment Grid') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    @if ($selectedAcademicYearId && $selectedTeacherId)
                        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                            <div class="border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-800">Step 2: Assign Teaching Load
                                        </h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Tick the subjects this teacher handles. Mark “Primary” where appropriate.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" id="select-all-rows"
                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                            Select All
                                        </button>

                                        <button type="button" id="clear-all-rows"
                                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                                            Clear All
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <input type="text" id="assignment-search"
                                        class="block w-full sm:max-w-md rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Search by class, subject code, or subject name...">

                                    <a href="{{ route('admin.subjects.manage-teachers') }}"
                                        class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                        ← Change Teacher / Academic Year
                                    </a>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.subjects.bulk-save-teachers') }}">
                                @csrf

                                <input type="hidden" name="teacher_id" value="{{ $selectedTeacherId }}">
                                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">

                                <div class="space-y-6 p-6">
                                    @forelse ($classes as $class)
                                        @php
                                            $classSubjectRows = $classSubjectsMap->get($class->id, collect());
                                        @endphp

                                        <div class="rounded-2xl border border-gray-200 overflow-hidden">
                                            <div class="bg-slate-100 px-4 py-3 border-b border-gray-200">
                                                <div
                                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                    <div>
                                                        <h5 class="text-lg font-semibold text-slate-800">
                                                            {{ $class->name }}</h5>
                                                        <p class="text-sm text-slate-600">
                                                            {{ $class->academicYear->year_name ?? 'No Academic Year' }}
                                                        </p>
                                                    </div>

                                                    <div class="text-sm text-slate-500">
                                                        {{ $classSubjectRows->count() }} subject(s) assigned to this
                                                        class
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($classSubjectRows->count() > 0)
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                                    Assign
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                                    Subject Code
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                                    Subject Name
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                                    Type
                                                                </th>
                                                                <th
                                                                    class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                                    Primary
                                                                </th>
                                                            </tr>
                                                        </thead>

                                                        <tbody class="divide-y divide-gray-100 bg-white">
                                                            @foreach ($classSubjectRows as $classSubject)
                                                                @php
                                                                    $subject = $classSubject->subject;
                                                                    $assignmentKey = $class->id . '_' . $subject->id;
                                                                    $existingAssignment = $existingTeacherAssignments->get(
                                                                        $assignmentKey,
                                                                    );

                                                                    $isSelected = old(
                                                                        "assignments.{$class->id}.{$subject->id}.selected",
                                                                        $existingAssignment ? 1 : 0,
                                                                    );

                                                                    $isPrimary = old(
                                                                        "assignments.{$class->id}.{$subject->id}.is_primary",
                                                                        $existingAssignment &&
                                                                        $existingAssignment->is_primary
                                                                            ? 1
                                                                            : 0,
                                                                    );
                                                                @endphp

                                                                <tr class="assignment-row"
                                                                    data-assignment-text="{{ strtolower($class->name . ' ' . $subject->code . ' ' . $subject->name) }}">
                                                                    <td class="px-4 py-4 align-middle">
                                                                        <input type="checkbox"
                                                                            name="assignments[{{ $class->id }}][{{ $subject->id }}][selected]"
                                                                            value="1"
                                                                            class="assignment-checkbox h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                                            {{ $isSelected ? 'checked' : '' }}>
                                                                    </td>

                                                                    <td
                                                                        class="px-4 py-4 align-middle text-sm font-semibold text-gray-900">
                                                                        {{ $subject->code }}
                                                                    </td>

                                                                    <td
                                                                        class="px-4 py-4 align-middle text-sm text-gray-800">
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
                                                                        <input type="checkbox"
                                                                            name="assignments[{{ $class->id }}][{{ $subject->id }}][is_primary]"
                                                                            value="1"
                                                                            class="primary-checkbox h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                                            {{ $isPrimary ? 'checked' : '' }}>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="p-4 text-sm text-gray-500 bg-gray-50">
                                                    No class-subject assignments found for this class in the selected
                                                    academic year.
                                                    Assign subjects to the class first.
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div
                                            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-500">
                                            No classes found for the selected academic year.
                                        </div>
                                    @endforelse
                                </div>

                                <div
                                    class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <a href="{{ route('admin.subjects.index') }}"
                                        class="text-sm text-gray-600 hover:text-indigo-600 font-medium">
                                        ← Back to Subjects
                                    </a>

                                    <x-primary-button>
                                        {{ __('Save Teacher Assignments') }}
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div
                            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-500">
                            Select an academic year and teacher first to manage teaching assignments.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAllBtn = document.getElementById('select-all-rows');
                const clearAllBtn = document.getElementById('clear-all-rows');
                const searchInput = document.getElementById('assignment-search');

                const getAssignmentCheckboxes = () => Array.from(document.querySelectorAll('.assignment-checkbox'));

                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function() {
                        getAssignmentCheckboxes().forEach(cb => cb.checked = true);
                    });
                }

                if (clearAllBtn) {
                    clearAllBtn.addEventListener('click', function() {
                        getAssignmentCheckboxes().forEach(cb => cb.checked = false);
                        document.querySelectorAll('.primary-checkbox').forEach(cb => cb.checked = false);
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const term = this.value.toLowerCase().trim();
                        const rows = document.querySelectorAll('.assignment-row');

                        rows.forEach(row => {
                            const text = row.dataset.assignmentText || '';
                            row.style.display = text.includes(term) ? '' : 'none';
                        });
                    });
                }

                document.querySelectorAll('.primary-checkbox').forEach(primaryCheckbox => {
                    primaryCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            const row = this.closest('tr');
                            const assignCheckbox = row.querySelector('.assignment-checkbox');
                            if (assignCheckbox) {
                                assignCheckbox.checked = true;
                            }
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
