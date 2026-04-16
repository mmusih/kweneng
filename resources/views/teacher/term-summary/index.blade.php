<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-violet-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Term Summary Entry
                </h2>
                <p class="text-violet-100 text-sm mt-1">
                    Enter overall attendance, punctuality, and behaviour for reports
                </p>
            </div>
            <a href="{{ route('teacher.dashboard') }}" class="text-white hover:text-violet-100 text-sm font-medium">
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
                    No active academic year found.
                </div>
            @elseif(!$activeTerm)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    No active term found.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                    <form method="GET" action="{{ route('teacher.term-summary.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" name="class_id"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
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

                            <div class="md:col-span-2 flex items-end">
                                <button type="submit"
                                    class="w-full md:w-auto bg-violet-600 hover:bg-violet-700 text-white font-semibold py-2.5 px-6 rounded-lg">
                                    Load Students
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($selectedClassId)
                    <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-6">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Class Term Summary</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $activeAcademicYear->year_name }} • {{ $activeTerm->name }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3 text-xs">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full bg-violet-100 text-violet-700 font-semibold">
                                    Fast Entry Mode
                                </span>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-semibold">
                                    Press Enter to move down
                                </span>
                            </div>
                        </div>

                        @if ($students->count() > 0)
                            <form method="POST" action="{{ route('teacher.term-summary.store') }}"
                                id="term-summary-form">
                                @csrf

                                <input type="hidden" name="class_id" value="{{ $selectedClassId }}">

                                <div class="mb-6 rounded-xl border border-violet-200 bg-violet-50 p-4">
                                    <label for="attendance_total_days"
                                        class="block text-sm font-semibold text-gray-800">
                                        Total Number of Days for the Term
                                    </label>
                                    <div class="mt-2 flex flex-col md:flex-row md:items-center gap-3">
                                        <input type="number" id="attendance_total_days" name="attendance_total_days"
                                            min="1" value="{{ old('attendance_total_days', $termTotalDays) }}"
                                            class="block w-full md:w-48 rounded-lg border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 text-center font-semibold"
                                            required>
                                        <p class="text-xs text-gray-600">
                                            Each student's days present must not exceed this value.
                                        </p>
                                    </div>
                                </div>

                                <div class="overflow-x-auto rounded-xl border border-gray-200">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-100 sticky top-0 z-10">
                                            <tr>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-28">
                                                    Adm No
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase min-w-[220px]">
                                                    Student Name
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-36">
                                                    Days Present
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-40">
                                                    Punctuality
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase w-40">
                                                    Behaviour
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase min-w-[220px]">
                                                    Remarks
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($students as $index => $student)
                                                <tr
                                                    class="data-row {{ $index % 2 === 0 ? 'bg-gray-50' : 'bg-white' }} hover:bg-violet-50 transition">
                                                    <td class="px-4 py-3 font-medium text-gray-800 align-middle">
                                                        {{ $student->admission_no }}
                                                    </td>

                                                    <td class="px-4 py-3 text-gray-900 align-middle">
                                                        {{ $student->user->name }}
                                                        <input type="hidden"
                                                            name="students[{{ $index }}][student_id]"
                                                            value="{{ $student->id }}">
                                                    </td>

                                                    <td class="px-4 py-2 align-middle">
                                                        <input type="number"
                                                            name="students[{{ $index }}][attendance_days_present]"
                                                            min="0"
                                                            max="{{ old('attendance_total_days', $termTotalDays) }}"
                                                            value="{{ old("students.$index.attendance_days_present", $student->existing_attendance_days_present) }}"
                                                            placeholder="Days"
                                                            class="fast-input attendance-input w-full px-3 py-2 rounded-md border border-gray-300 focus:border-violet-500 focus:ring-1 focus:ring-violet-500 text-center font-semibold">
                                                    </td>

                                                    <td class="px-4 py-2 align-middle">
                                                        <select name="students[{{ $index }}][punctuality]"
                                                            class="fast-input w-full px-2 py-2 rounded-md border border-gray-300 focus:border-violet-500 focus:ring-1 focus:ring-violet-500">
                                                            <option value="">Select</option>
                                                            @foreach (\App\Models\StudentTermSummary::PUNCTUALITY_LABELS as $label)
                                                                <option value="{{ $label }}"
                                                                    {{ old("students.$index.punctuality", $student->existing_punctuality) === $label ? 'selected' : '' }}>
                                                                    {{ $label }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>

                                                    <td class="px-4 py-2 align-middle">
                                                        <select name="students[{{ $index }}][behaviour]"
                                                            class="fast-input w-full px-2 py-2 rounded-md border border-gray-300 focus:border-violet-500 focus:ring-1 focus:ring-violet-500">
                                                            <option value="">Select</option>
                                                            @foreach (\App\Models\StudentTermSummary::BEHAVIOUR_LABELS as $label)
                                                                <option value="{{ $label }}"
                                                                    {{ old("students.$index.behaviour", $student->existing_behaviour) === $label ? 'selected' : '' }}>
                                                                    {{ $label }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>

                                                    <td class="px-4 py-2 align-middle">
                                                        <input type="text"
                                                            name="students[{{ $index }}][remarks]"
                                                            value="{{ old("students.$index.remarks", $student->existing_summary_remarks) }}"
                                                            placeholder="Optional..."
                                                            class="fast-input w-full px-3 py-2 rounded-md border border-gray-300 focus:border-violet-500 focus:ring-1 focus:ring-violet-500">
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
                                        class="bg-violet-600 hover:bg-violet-700 text-white font-semibold py-2.5 px-6 rounded-lg shadow-sm">
                                        Save Term Summary
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const totalDaysInput = document.getElementById('attendance_total_days');
            const attendanceInputs = document.querySelectorAll('.attendance-input');
            const fastInputs = Array.from(document.querySelectorAll('.fast-input'));
            const rows = document.querySelectorAll('.data-row');

            function syncAttendanceMax() {
                const total = totalDaysInput?.value || '';
                attendanceInputs.forEach(input => {
                    if (total !== '') {
                        input.setAttribute('max', total);
                    } else {
                        input.removeAttribute('max');
                    }
                });
            }

            function clearRowHighlights() {
                rows.forEach(row => {
                    row.classList.remove('ring-1', 'ring-violet-300', 'bg-violet-100');
                });
            }

            function highlightCurrentRow(el) {
                clearRowHighlights();
                const row = el.closest('.data-row');
                if (row) {
                    row.classList.add('ring-1', 'ring-violet-300', 'bg-violet-100');
                }
            }

            if (totalDaysInput) {
                totalDaysInput.addEventListener('input', syncAttendanceMax);
                syncAttendanceMax();
            }

            fastInputs.forEach((input, index) => {
                input.addEventListener('focus', function() {
                    highlightCurrentRow(this);
                    this.select?.();
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const next = fastInputs[index + 1];
                        if (next) {
                            next.focus();
                        }
                    }
                });

                if (input.classList.contains('attendance-input')) {
                    input.addEventListener('blur', function() {
                        const total = parseInt(totalDaysInput?.value || '0', 10);
                        const current = parseInt(this.value || '0', 10);

                        if (total > 0 && current > total) {
                            this.value = total;
                        }
                    });
                }
            });
        });
    </script>
</x-app-layout>
