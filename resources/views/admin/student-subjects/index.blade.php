<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-4 shadow-xl flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-white leading-tight">
                    Student Subject Assignments
                </h2>
                <p class="text-blue-100 text-sm mt-1">
                    Manage assignments with faster filtering, better visibility, and bulk actions.
                </p>
            </div>

            <a href="{{ route('admin.student-subjects.create') }}"
                class="inline-flex items-center rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-white/25 border border-white/20">
                Assign Students
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-6 text-gray-900">
                    <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-gray-600">
                            <a href="{{ route('admin.dashboard') }}"
                                class="font-medium text-blue-600 hover:text-indigo-700">Dashboard</a>
                            <span class="mx-2 text-gray-400">/</span>
                            <span class="font-medium text-gray-800">Student Subject Assignments</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.dashboard') }}"
                                class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                Back to Dashboard
                            </a>
                            <a href="{{ route('admin.student-subjects.create') }}"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow">
                                New Assignment
                            </a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 shadow-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Visible Assignments
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $studentSubjects->total() }}</p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br from-purple-50 to-fuchsia-50 border border-purple-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-purple-700">Current Page</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $studentSubjects->currentPage() }}</p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Per Page</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $studentSubjects->perPage() }}</p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Selected</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900" id="selected-count">0</p>
                        </div>
                    </div>

                    <div
                        class="mb-8 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-6 shadow-sm">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Filter Assignments</h3>
                            <p class="text-sm text-gray-500 mt-1">Use filters to narrow the list before bulk actions.
                            </p>
                        </div>

                        <form method="GET" action="{{ route('admin.student-subjects.index') }}"
                            class="grid grid-cols-1 md:grid-cols-6 gap-4">
                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                    <option value="">All Academic Years</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                    <option value="">All Classes</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="subject_id" :value="__('Subject')" />
                                <select id="subject_id" name="subject_id"
                                    class="block mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                    <option value="">All Subjects</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}"
                                            {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }} ({{ $subject->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="teacher_id" :value="__('Teacher')" />
                                <select id="teacher_id" name="teacher_id"
                                    class="block mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                                    <option value="">All Teachers</option>
                                    @foreach ($teachers as $teacher)
                                        <option value="{{ $teacher->id }}"
                                            {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="search" :value="__('Search')" />
                                <x-text-input id="search" class="block mt-1 w-full rounded-xl" type="text"
                                    name="search" :value="request('search')"
                                    placeholder="Student, admission no, subject, or teacher" />
                            </div>

                            <div class="md:col-span-6 flex flex-wrap items-end gap-3 pt-2">
                                <button type="submit"
                                    class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                    Apply Filters
                                </button>

                                <a href="{{ route('admin.student-subjects.index') }}"
                                    class="inline-flex items-center rounded-xl bg-gray-500 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-gray-600">
                                    Clear
                                </a>
                            </div>
                        </form>
                    </div>

                    @if ($studentSubjects->count() > 0)
                        <form action="{{ route('admin.student-subjects.bulk-destroy') }}" method="POST"
                            onsubmit="return confirmBulkRemoval();">
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="academic_year_id" value="{{ request('academic_year_id') }}">
                            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                            <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                            <input type="hidden" name="teacher_id" value="{{ request('teacher_id') }}">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="page" value="{{ request('page', 1) }}">

                            <div
                                class="mb-4 flex flex-col gap-3 rounded-2xl border border-red-100 bg-gradient-to-r from-red-50 to-pink-50 p-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" id="select-all"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Select all on this
                                            page</span>
                                    </label>

                                    <span
                                        class="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm font-semibold text-red-700 shadow-sm">
                                        Bulk remove selected assignments
                                    </span>
                                </div>

                                <button type="submit"
                                    class="inline-flex items-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-red-700">
                                    Remove Selected
                                </button>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gradient-to-r from-slate-100 to-gray-100">
                                        <tr>
                                            <th
                                                class="px-4 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                <input type="checkbox" id="header-select-all"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Student
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Subject
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Teacher
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Class
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Academic Year
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Type
                                            </th>
                                            <th
                                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($studentSubjects as $assignment)
                                            <tr class="hover:bg-indigo-50/40 transition">
                                                <td class="px-4 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="assignment_ids[]"
                                                        value="{{ $assignment->id }}"
                                                        class="assignment-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-semibold text-gray-900">
                                                        {{ $assignment->student->user->name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        {{ $assignment->student->admission_no }}
                                                    </div>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-700">
                                                        {{ $assignment->subject->name }}
                                                        ({{ $assignment->subject->code }})
                                                    </span>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $assignment->teacher->user->name ?? '—' }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $assignment->class->name }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $assignment->academicYear->year_name }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if ($assignment->is_elective)
                                                        <span
                                                            class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                                            Elective
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-800">
                                                            Core
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <form
                                                        action="{{ route('admin.student-subjects.destroy', $assignment) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="academic_year_id"
                                                            value="{{ request('academic_year_id') }}">
                                                        <input type="hidden" name="class_id"
                                                            value="{{ request('class_id') }}">
                                                        <input type="hidden" name="subject_id"
                                                            value="{{ request('subject_id') }}">
                                                        <input type="hidden" name="teacher_id"
                                                            value="{{ request('teacher_id') }}">
                                                        <input type="hidden" name="search"
                                                            value="{{ request('search') }}">
                                                        <input type="hidden" name="page"
                                                            value="{{ request('page', 1) }}">

                                                        <button type="submit"
                                                            class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-100">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    @else
                        <div
                            class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center shadow-sm">
                            <div
                                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">No assignments found</h4>
                            <p class="mt-2 text-sm text-gray-500">Try changing your filters or create a new assignment.
                            </p>
                        </div>
                    @endif

                    <div class="mt-6">
                        {{ $studentSubjects->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const headerSelectAll = document.getElementById('header-select-all');
            const checkboxes = document.querySelectorAll('.assignment-checkbox');
            const selectedCount = document.getElementById('selected-count');

            function updateCount() {
                const checked = document.querySelectorAll('.assignment-checkbox:checked').length;
                if (selectedCount) {
                    selectedCount.textContent = checked;
                }

                const allChecked = checkboxes.length > 0 && checked === checkboxes.length;

                if (selectAll) selectAll.checked = allChecked;
                if (headerSelectAll) headerSelectAll.checked = allChecked;
            }

            function toggleAll(source) {
                checkboxes.forEach(cb => cb.checked = source.checked);
                updateCount();
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    toggleAll(this);
                });
            }

            if (headerSelectAll) {
                headerSelectAll.addEventListener('change', function() {
                    toggleAll(this);
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateCount);
            });

            updateCount();
        });

        function confirmBulkRemoval() {
            const checked = document.querySelectorAll('.assignment-checkbox:checked').length;

            if (checked === 0) {
                alert('Please select at least one assignment to remove.');
                return false;
            }

            return confirm(`Are you sure you want to remove ${checked} assignment(s)?`);
        }
    </script>
</x-app-layout>
