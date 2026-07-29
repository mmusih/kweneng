<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-emerald-600 to-teal-700 rounded-lg shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">Attendance Register</h2>
                <p class="text-emerald-100 text-sm mt-1">Croxley-style daily class register linked to the school calendar</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('teacher.attendance.summary', ['class_id' => $selectedClassId]) }}" class="inline-flex items-center px-4 py-2 bg-white/15 text-white rounded-md text-sm font-semibold hover:bg-white/25">
                    Summary
                </a>
                <a href="{{ route('teacher.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white text-emerald-700 rounded-md text-sm font-semibold hover:bg-emerald-50">
                    Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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
                <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-100">
                    <form method="GET" action="{{ route('teacher.attendance.index') }}">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-2">
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" name="class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                                    <option value="">Select class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                                            {{ $class->name }} {{ $class->academicYear?->year_name ? ' - ' . $class->academicYear->year_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="attendance_date" class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" id="attendance_date" name="attendance_date" value="{{ $selectedDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                            </div>

                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-4 rounded-md">
                                    Load Register
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($selectedClassId && $selectedClass)
                    @php
                        $disabledRegister = (bool) $holiday || (bool) $dateWarning;
                        $presentCount = $students->where('existing_status', \App\Models\Attendance::STATUS_PRESENT)->count();
                        $absentCount = $students->where('existing_status', \App\Models\Attendance::STATUS_ABSENT)->count();
                        $lateCount = $students->where('existing_status', \App\Models\Attendance::STATUS_LATE)->count();
                        $excusedCount = $students->where('existing_status', \App\Models\Attendance::STATUS_EXCUSED)->count();
                        $parentNoticeCount = $students->filter(fn ($student) => $student->parent_absence_notice)->count();
                    @endphp

                    @if ($holiday)
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-5 text-yellow-900">
                            <div class="font-semibold">Holiday: {{ $holiday['title'] }}</div>
                            <p class="text-sm mt-1">This date is marked as a holiday in the school calendar. The register is automatically shown as a holiday and attendance cannot be saved for this date.</p>
                        </div>
                    @endif

                    @if ($dateWarning)
                        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-red-900">
                            <div class="font-semibold">Date outside active term</div>
                            <p class="text-sm mt-1">{{ $dateWarning }}</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Class</p>
                            <p class="text-xl font-bold text-gray-900 mt-1">{{ $selectedClass->name }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Learners</p>
                            <p class="text-xl font-bold text-gray-900 mt-1">{{ $students->count() }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Present</p>
                            <p class="text-xl font-bold text-green-700 mt-1">{{ $presentCount }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Absent</p>
                            <p class="text-xl font-bold text-red-700 mt-1">{{ $absentCount }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Late / Excused</p>
                            <p class="text-xl font-bold text-amber-700 mt-1">{{ $lateCount }} / {{ $excusedCount }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Parent Notices</p>
                            <p class="text-xl font-bold text-indigo-700 mt-1">{{ $parentNoticeCount }}</p>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-100">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Daily Register</h3>
                                <p class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($selectedDate)->format('l, d M Y') }}. Parent absence notices appear beside the learner; the teacher still confirms the official status.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('teacher.attendance.summary', ['class_id' => $selectedClass->id]) }}" class="inline-flex items-center px-3 py-2 rounded-md bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-slate-200">View Summary</a>
                                @unless($disabledRegister)
                                    <button type="button" data-bulk-status="present" class="bulk-status px-3 py-2 rounded-md bg-green-600 text-white text-sm font-semibold hover:bg-green-700">All Present</button>
                                    <button type="button" data-bulk-status="absent" class="bulk-status px-3 py-2 rounded-md bg-red-600 text-white text-sm font-semibold hover:bg-red-700">All Absent</button>
                                    <button type="button" data-bulk-status="late" class="bulk-status px-3 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">All Late</button>
                                    <button type="button" data-bulk-status="excused" class="bulk-status px-3 py-2 rounded-md bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">All Excused</button>
                                @endunless
                            </div>
                        </div>

                        @if ($students->count() > 0)
                            <form method="POST" action="{{ route('teacher.attendance.store') }}" id="attendance-form">
                                @csrf
                                <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
                                <input type="hidden" name="attendance_date" value="{{ $selectedDate }}">

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No.</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admission</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Learner</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent Notice / Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @foreach ($students as $index => $student)
                                                @php
                                                    $notice = $student->parent_absence_notice;
                                                    $currentStatus = old("students.$index.status", $student->existing_status);
                                                @endphp
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ $student->admission_no }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900 min-w-[220px]">
                                                        <div class="font-semibold">{{ $student->user->name ?? 'Unnamed Student' }}</div>
                                                        @if ($notice)
                                                            <div class="mt-1 inline-flex items-center px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                                Parent reported absence
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-sm min-w-[300px]">
                                                        <input type="hidden" name="students[{{ $index }}][student_id]" value="{{ $student->id }}">
                                                        <input type="hidden" id="status-{{ $index }}" name="students[{{ $index }}][status]" value="{{ $currentStatus }}" data-status-input>

                                                        @if ($holiday)
                                                            <span class="inline-flex items-center px-3 py-2 rounded-md bg-yellow-100 text-yellow-800 font-semibold">H Holiday</span>
                                                        @else
                                                            <div class="flex flex-wrap gap-2" data-status-group="status-{{ $index }}">
                                                                @foreach (\App\Models\Attendance::statusLabels() as $statusValue => $statusLabel)
                                                                    @php
                                                                        $code = \App\Models\Attendance::statusCode($statusValue);
                                                                        $base = match ($statusValue) {
                                                                            \App\Models\Attendance::STATUS_PRESENT => 'green',
                                                                            \App\Models\Attendance::STATUS_ABSENT => 'red',
                                                                            \App\Models\Attendance::STATUS_LATE => 'amber',
                                                                            \App\Models\Attendance::STATUS_EXCUSED => 'blue',
                                                                            default => 'gray',
                                                                        };
                                                                    @endphp
                                                                    <button type="button" data-status-button data-status="{{ $statusValue }}" data-target="status-{{ $index }}" class="status-pill px-3 py-2 rounded-md border text-sm font-semibold transition {{ $currentStatus === $statusValue ? 'bg-' . $base . '-600 text-white border-' . $base . '-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}" {{ $disabledRegister ? 'disabled' : '' }}>
                                                                        {{ $code }} <span class="hidden sm:inline">{{ $statusLabel }}</span>
                                                                    </button>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-sm min-w-[320px]">
                                                        @if ($notice)
                                                            <div class="mb-3 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-indigo-900">
                                                                <div class="font-semibold text-sm">{{ $notice->reason }}</div>
                                                                <div class="text-xs mt-1">
                                                                    Reported by {{ $notice->parent->user->name ?? 'Parent' }}
                                                                    @if ($notice->expected_return_date)
                                                                        · Expected return {{ $notice->expected_return_date->format('d M Y') }}
                                                                    @endif
                                                                </div>
                                                                @if ($notice->note)
                                                                    <div class="text-xs mt-2 text-indigo-800">{{ $notice->note }}</div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                        <input type="text" name="students[{{ $index }}][remarks]" value="{{ old("students.$index.remarks", $student->existing_remarks) }}" placeholder="Optional teacher remarks" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" {{ $disabledRegister ? 'disabled' : '' }}>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @unless($disabledRegister)
                                    <div class="mt-6 flex justify-end">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-6 rounded-md">
                                            Save Official Register
                                        </button>
                                    </div>
                                @endunless
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
        document.addEventListener('DOMContentLoaded', function () {
            function activateButton(button) {
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;

                input.value = button.dataset.status;

                document.querySelectorAll('[data-target="' + targetId + '"]').forEach(function (item) {
                    item.className = 'status-pill px-3 py-2 rounded-md border text-sm font-semibold transition bg-white text-gray-700 border-gray-300 hover:bg-gray-50';
                });

                const status = button.dataset.status;
                const color = status === 'present' ? 'green' : status === 'absent' ? 'red' : status === 'late' ? 'amber' : 'blue';
                button.className = 'status-pill px-3 py-2 rounded-md border text-sm font-semibold transition bg-' + color + '-600 text-white border-' + color + '-600';
            }

            document.querySelectorAll('[data-status-button]').forEach(function (button) {
                button.addEventListener('click', function () {
                    activateButton(button);
                });
            });

            document.querySelectorAll('[data-bulk-status]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const status = button.dataset.bulkStatus;
                    document.querySelectorAll('[data-status-button][data-status="' + status + '"]').forEach(function (statusButton) {
                        activateButton(statusButton);
                    });
                });
            });
        });
    </script>

    <div class="hidden bg-green-600 border-green-600 bg-red-600 border-red-600 bg-amber-600 border-amber-600 bg-blue-600 border-blue-600"></div>
</x-app-layout>
