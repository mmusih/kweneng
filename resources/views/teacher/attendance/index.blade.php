<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Attendance Entry
                </h2>
                <p class="text-blue-100 text-sm mt-1">
                    Record daily attendance by class
                </p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="text-white hover:text-blue-100 text-sm font-medium">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (!$activeAcademicYear)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active academic year found. Please ask the administrator to activate an academic year.
                </div>
            @elseif(!$activeTerm)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active term found. Attendance cannot be recorded until a term is active.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form method="GET" action="{{ route('teacher.attendance.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" name="class_id"
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
                                <label for="attendance_date" class="block text-sm font-medium text-gray-700">Attendance
                                    Date</label>
                                <input type="date" id="attendance_date" name="attendance_date"
                                    value="{{ $selectedDate }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                            </div>

                            <div class="flex items-end">
                                <button type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md">
                                    Load Students
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($selectedClassId)
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Attendance Register</h3>
                                <p class="text-sm text-gray-500">
                                    Date: {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                                </p>
                            </div>
                        </div>

                        @if ($students->count() > 0)
                            <form method="POST" action="{{ route('teacher.attendance.store') }}">
                                @csrf

                                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">
                                <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                    Admission No</th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                    Student Name</th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                    Status</th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                    Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @foreach ($students as $index => $student)
                                                <tr>
                                                    <td class="px-4 py-3 text-sm text-gray-900">
                                                        {{ $student->admission_no }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-900">
                                                        {{ $student->user->name }}
                                                    </td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <input type="hidden"
                                                            name="students[{{ $index }}][student_id]"
                                                            value="{{ $student->id }}">

                                                        <select name="students[{{ $index }}][status]"
                                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                            required>
                                                            <option value="present"
                                                                {{ old("students.$index.status", $student->existing_status) === 'present' ? 'selected' : '' }}>
                                                                Present</option>
                                                            <option value="absent"
                                                                {{ old("students.$index.status", $student->existing_status) === 'absent' ? 'selected' : '' }}>
                                                                Absent</option>
                                                            <option value="late"
                                                                {{ old("students.$index.status", $student->existing_status) === 'late' ? 'selected' : '' }}>
                                                                Late</option>
                                                            <option value="excused"
                                                                {{ old("students.$index.status", $student->existing_status) === 'excused' ? 'selected' : '' }}>
                                                                Excused</option>
                                                        </select>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm">
                                                        <input type="text"
                                                            name="students[{{ $index }}][remarks]"
                                                            value="{{ old("students.$index.remarks", $student->existing_remarks) }}"
                                                            placeholder="Optional remarks"
                                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if ($errors->any())
                                    <div class="mt-4 rounded-lg bg-red-50 p-4 text-red-800">
                                        <ul class="list-disc pl-5 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="mt-6 flex justify-end">
                                    <button type="submit"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-md">
                                        Save Attendance
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="text-gray-500">No students found for the selected class.</p>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
