<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 p-4 shadow-xl flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-white leading-tight">
                    Manage Students
                </h2>
                <p class="text-blue-100 text-sm mt-1">
                    Search, filter, print login slips, and safely remove test students without losing page context.
                </p>
            </div>

            <a href="{{ route('admin.students.create') }}"
                class="inline-flex items-center rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-white/25 border border-white/20">
                Add New Student
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
                            <span class="font-medium text-gray-800">Manage Students</span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.dashboard') }}"
                                class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                                Back to Dashboard
                            </a>
                            <a href="{{ route('admin.students.create') }}"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow">
                                New Student
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
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Visible Students</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $students->total() }}</p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br from-purple-50 to-fuchsia-50 border border-purple-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-purple-700">Current Page</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $students->currentPage() }}</p>
                        </div>

                        <div
                            class="rounded-2xl bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-100 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Per Page</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $students->perPage() }}</p>
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
                            <h3 class="text-lg font-bold text-gray-900">Filter Students</h3>
                            <p class="text-sm text-gray-500 mt-1">Find test students quickly before deleting them.</p>
                        </div>

                        <form method="GET" action="{{ route('admin.students.index') }}"
                            class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="search" :value="__('Search by Name or Admission No')" />
                                <x-text-input id="search" class="block mt-1 w-full rounded-xl" type="text"
                                    name="search" :value="request('search')" placeholder="Name or Admission No" />
                            </div>

                            <div>
                                <x-input-label for="class_id" :value="__('Filter by Class')" />
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

                            <div class="flex items-end gap-2">
                                <button type="submit"
                                    class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                    Filter
                                </button>

                                <a href="{{ route('admin.students.index') }}"
                                    class="inline-flex items-center rounded-xl bg-gray-500 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-gray-600">
                                    Clear
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="mb-4 text-sm text-gray-600">
                        Showing {{ $students->firstItem() ?? 0 }} to {{ $students->lastItem() ?? 0 }} of
                        {{ $students->total() }} students
                    </div>

                    @if ($students->count() > 0)
                        <form id="students-bulk-form" method="POST">
                            @csrf

                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="class_id" value="{{ request('class_id') }}">
                            <input type="hidden" name="page" value="{{ request('page', 1) }}">

                            <div
                                class="mb-4 flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 p-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" id="select-all"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm font-medium text-gray-700">Select all on this
                                            page</span>
                                    </label>

                                    <span
                                        class="inline-flex items-center rounded-full bg-white px-3 py-1 text-sm font-semibold text-indigo-700 shadow-sm">
                                        Bulk actions for selected students
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="submit"
    formaction="{{ route('admin.students.print-logins') }}"
    formmethod="POST"
    onclick="return confirmPrintLogins();"
    class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700">
    Print Login Slips
</button>

<button type="submit"
    formaction="{{ route('admin.students.bulk-delete') }}"
    formmethod="POST"
    name="_method"
    value="DELETE"
    onclick="return confirmBulkDelete();"
    class="inline-flex items-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-red-700">
    Delete Selected
</button>
                                </div>
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
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Student</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Admission No</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Class</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Enrollment Status</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Contact</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status</th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($students as $student)
                                            @php
                                                $currentAcademicYear = \App\Models\AcademicYear::where(
                                                    'status',
                                                    'open',
                                                )->first();
                                                $isEnrolled = false;

                                                if ($currentAcademicYear && $student->currentClass) {
                                                    $isEnrolled = \App\Models\StudentClassHistory::where(
                                                        'student_id',
                                                        $student->id,
                                                    )
                                                        ->where('class_id', $student->currentClass->id)
                                                        ->where('academic_year_id', $currentAcademicYear->id)
                                                        ->where('status', 'active')
                                                        ->exists();
                                                }
                                            @endphp

                                            <tr class="hover:bg-indigo-50/40 transition">
                                                <td class="px-4 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="student_ids[]"
                                                        value="{{ $student->id }}"
                                                        class="student-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        @if ($student->photo)
                                                            <img class="h-10 w-10 rounded-full object-cover"
                                                                src="{{ Storage::url($student->photo) }}"
                                                                alt="{{ $student->user->name }}">
                                                        @else
                                                            <div
                                                                class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                <span
                                                                    class="text-gray-500">{{ strtoupper(substr($student->user->name, 0, 1)) }}</span>
                                                            </div>
                                                        @endif

                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900">
                                                                {{ $student->user->name }}</div>
                                                            <div class="text-sm text-gray-500">
                                                                {{ $student->user->email }}</div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $student->admission_no }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $student->currentClass ? $student->currentClass->name : 'Not Assigned' }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if ($isEnrolled)
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Enrolled
                                                        </span>
                                                    @else
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                            Not Enrolled
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ ucfirst($student->gender) }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if ($student->user->status === 'active')
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Active
                                                        </span>
                                                    @else
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            Inactive
                                                        </span>
                                                    @endif

                                                    @if ($student->user->must_change_password)
                                                        <span
                                                            class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                                            Must Change Password
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex items-center gap-3 flex-wrap">
                                                        <a href="{{ route('admin.students.show', $student) }}"
                                                            class="text-blue-600 hover:text-blue-900">
                                                            View
                                                        </a>

                                                        <a href="{{ route('admin.students.edit', $student) }}"
                                                            class="text-indigo-600 hover:text-indigo-900">
                                                            Edit
                                                        </a>

                                                        <form
                                                            action="{{ route('admin.students.reset-password', $student) }}"
                                                            method="POST"
                                                            onsubmit="return confirm('Reset password for this student?');">
                                                            @csrf
                                                            <button type="submit"
                                                                class="text-orange-600 hover:text-orange-900">
                                                                Reset Password
                                                            </button>
                                                        </form>

                                                        <form action="{{ route('admin.students.destroy', $student) }}"
                                                            method="POST"
                                                            onsubmit="return confirm('Are you sure you want to delete this student?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="search"
                                                                value="{{ request('search') }}">
                                                            <input type="hidden" name="class_id"
                                                                value="{{ request('class_id') }}">
                                                            <input type="hidden" name="page"
                                                                value="{{ request('page', 1) }}">

                                                            <button type="submit"
                                                                class="text-red-600 hover:text-red-900">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8"
                                                    class="px-6 py-4 text-center text-sm text-gray-500">
                                                    No students found.
                                                </td>
                                            </tr>
                                        @endforelse
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
                                        d="M17 20h5V4H2v16h5m10 0v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2m12 0H7m10 0h-2m-8 0H5m7-10a4 4 0 110-8 4 4 0 010 8z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-900">No students found</h4>
                            <p class="mt-2 text-sm text-gray-500">Try changing your filters or add a new student.</p>
                        </div>
                    @endif

                    <div class="mt-6">
                        {{ $students->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const headerSelectAll = document.getElementById('header-select-all');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            const selectedCount = document.getElementById('selected-count');

            function updateCount() {
                const checked = document.querySelectorAll('.student-checkbox:checked').length;

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

        function selectedStudentsCount() {
            return document.querySelectorAll('.student-checkbox:checked').length;
        }

        function confirmBulkDelete() {
            const checked = selectedStudentsCount();

            if (checked === 0) {
                alert('Please select at least one student.');
                return false;
            }

            return confirm(`Are you sure you want to delete ${checked} selected student(s)?`);
        }

        function confirmPrintLogins() {
            const checked = selectedStudentsCount();

            if (checked === 0) {
                alert('Please select at least one student.');
                return false;
            }

            return confirm(`Generate new temporary passwords and print login slips for ${checked} selected student(s)? Existing passwords for those students will be reset.`);
        }
    </script>
</x-app-layout>