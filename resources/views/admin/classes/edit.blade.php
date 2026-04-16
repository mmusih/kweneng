<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <h2 class="font-semibold text-2xl text-white leading-tight">
                Edit Class
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 text-sm text-gray-600">
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                        <span class="mx-2">/</span>
                        <a href="{{ route('admin.classes.index') }}" class="hover:text-indigo-600">Classes</a>
                        <span class="mx-2">/</span>
                        <span class="text-gray-800 font-medium">Edit Class</span>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.classes.update', $class) }}">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="student_search" value="{{ request('student_search') }}">
                        <input type="hidden" name="page" value="{{ request('page', 1) }}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="name" :value="__('Class Name')" />
                                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                                    :value="old('name', $class->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="level" :value="__('Level')" />
                                <x-text-input id="level" class="block mt-1 w-full" type="number" name="level"
                                    :value="old('level', $class->level)" required min="1" max="12" />
                                <x-input-error :messages="$errors->get('level')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="academic_year_id" :value="__('Academic Year')" />
                                <select id="academic_year_id" name="academic_year_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="">Select academic year</option>
                                    @foreach ($academicYears as $year)
                                        <option value="{{ $year->id }}"
                                            {{ old('academic_year_id', $class->academic_year_id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->year_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('academic_year_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="class_teacher_id" :value="__('Class Teacher')" />
                                <select id="class_teacher_id" name="class_teacher_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">No class teacher assigned</option>
                                    @foreach ($classTeachers as $teacher)
                                        <option value="{{ $teacher->id }}"
                                            {{ old('class_teacher_id', $class->class_teacher_id) == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->user->name }}
                                            @if ($teacher->user->role === 'headmaster')
                                                - Headmaster
                                            @else
                                                - Teacher
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('class_teacher_id')" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">
                                    Only the assigned class teacher will manage attendance, punctuality, and behaviour
                                    for this class.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.dashboard') }}" class="mr-3 text-gray-600 hover:text-gray-800">
                                Back to Dashboard
                            </a>

                            <a href="{{ route('admin.classes.index') }}"
                                class="mr-4 text-gray-600 hover:text-gray-800">
                                Back to Classes
                            </a>

                            <x-primary-button>
                                {{ __('Update Class') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-xl font-semibold">Students in this Class</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Search, select, and remove students from this class without deleting their records.
                            </p>
                        </div>

                        <span
                            class="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }}
                        </span>
                    </div>

                    @if ($errors->has('remove_student'))
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800">
                            {{ $errors->first('remove_student') }}
                        </div>
                    @endif

                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <form method="GET" action="{{ route('admin.classes.edit', $class) }}"
                            class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="md:col-span-3">
                                <x-input-label for="student_search" :value="__('Search Students')" />
                                <x-text-input id="student_search" class="block mt-1 w-full" type="text"
                                    name="student_search" :value="request('student_search')"
                                    placeholder="Search by student name or admission number" />
                            </div>

                            <div class="flex items-end gap-2">
                                <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Search
                                </button>

                                <a href="{{ route('admin.classes.edit', $class) }}"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    Clear
                                </a>
                            </div>
                        </form>
                    </div>

                    @if ($students->count() > 0)
                        <form method="POST" action="{{ route('admin.classes.bulk-remove-students', $class) }}"
                            onsubmit="return confirmBulkRemove();">
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="student_search" value="{{ request('student_search') }}">
                            <input type="hidden" name="page" value="{{ request('page', 1) }}">

                            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex items-center gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" id="select-all-students"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">Select all on this page</span>
                                    </label>

                                    <span id="selected-count" class="text-sm text-gray-600">0 selected</span>
                                </div>

                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Remove Selected
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Select
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Student Name
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Admission No
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Gender
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($students as $student)
                                            <tr>
                                                <td class="px-4 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="student_ids[]"
                                                        value="{{ $student->id }}"
                                                        class="student-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ $student->user->name ?? 'N/A' }}
                                                    </div>
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $student->admission_no }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ ucfirst($student->gender ?? '-') }}
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <form
                                                        action="{{ route('admin.classes.remove-student', [$class, $student]) }}"
                                                        method="POST" class="inline-block"
                                                        onsubmit="return confirm('Are you sure you want to remove this student from the class?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="student_search"
                                                            value="{{ request('student_search') }}">
                                                        <input type="hidden" name="page"
                                                            value="{{ request('page', 1) }}">
                                                        <button type="submit"
                                                            class="text-red-600 hover:text-red-900">
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

                        <div class="mt-4">
                            {{ $students->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500">
                            No students found for this class.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all-students');
            const checkboxes = Array.from(document.querySelectorAll('.student-checkbox'));
            const selectedCount = document.getElementById('selected-count');

            function updateSelectedCount() {
                const count = checkboxes.filter(cb => cb.checked).length;
                if (selectedCount) {
                    selectedCount.textContent = `${count} selected`;
                }

                if (selectAll) {
                    const allChecked = checkboxes.length > 0 && checkboxes.every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = selectAll.checked;
                    });
                    updateSelectedCount();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
        });

        function confirmBulkRemove() {
            const selected = document.querySelectorAll('.student-checkbox:checked').length;

            if (selected === 0) {
                alert('Please select at least one student.');
                return false;
            }

            return confirm(`Are you sure you want to remove ${selected} selected student(s) from this class?`);
        }
    </script>
</x-app-layout>
