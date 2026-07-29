<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-gradient-to-r from-slate-700 to-slate-900 rounded-lg shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-white leading-tight">Attendance Summary</h2>
                <p class="text-slate-200 text-sm mt-1">Online class register summaries, holiday days, and downloadable records</p>
            </div>
            <a href="{{ route('teacher.attendance.index', ['class_id' => $selectedClassId]) }}" class="inline-flex items-center px-4 py-2 bg-white text-slate-800 rounded-md text-sm font-semibold hover:bg-slate-100">
                Daily Register
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (!$activeAcademicYear || !$activeTerm)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg">
                    Attendance summaries need an active academic year and active term.
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <form method="GET" action="{{ route('teacher.attendance.summary') }}">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="md:col-span-2">
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" name="class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}" {{ (string) $selectedClassId === (string) $class->id ? 'selected' : '' }}>
                                            {{ $class->name }} {{ $class->academicYear?->year_name ? ' - ' . $class->academicYear->year_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">From</label>
                                <input type="date" id="from_date" name="from_date" value="{{ $fromDate->toDateString() }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            </div>
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">To</label>
                                <input type="date" id="to_date" name="to_date" value="{{ $toDate->toDateString() }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full px-4 py-2 bg-slate-800 text-white rounded-md font-semibold hover:bg-slate-900">Load</button>
                            </div>
                        </div>
                        <label class="inline-flex items-center mt-4 text-sm text-gray-700">
                            <input type="checkbox" name="include_weekends" value="1" class="rounded border-gray-300 text-slate-700 shadow-sm focus:ring-slate-500" {{ $includeWeekends ? 'checked' : '' }}>
                            <span class="ml-2">Include weekends in the register view</span>
                        </label>
                    </form>
                </div>

                @if ($selectedClass)
                    @php
                        $query = [
                            'class_id' => $selectedClass->id,
                            'from_date' => $fromDate->toDateString(),
                            'to_date' => $toDate->toDateString(),
                            'include_weekends' => $includeWeekends ? 1 : 0,
                        ];
                    @endphp

                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Class</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">{{ $selectedClass->name }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Learners</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">{{ $students->count() }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Recorded Days</p>
                            <p class="text-lg font-bold text-indigo-700 mt-1">{{ $recordedTeachingDates->count() }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Unmarked Days</p>
                            <p class="text-lg font-bold text-amber-700 mt-1">{{ $unmarkedTeachingDays }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Holidays</p>
                            <p class="text-lg font-bold text-yellow-700 mt-1">{{ $holidayDates->count() }}</p>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                            <p class="text-xs text-gray-500 uppercase">Attendance Rate</p>
                            <p class="text-lg font-bold text-green-700 mt-1">{{ $classAttendancePercentage !== null ? number_format($classAttendancePercentage, 1) . '%' : 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Download and Print</h3>
                                <p class="text-sm text-gray-500">PDF and CSV use the same filtered date range shown on screen.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a target="_blank" href="{{ route('teacher.attendance.print', $query) }}" class="px-3 py-2 bg-gray-100 text-gray-800 rounded-md text-sm font-semibold hover:bg-gray-200">Print View</a>
                                <a href="{{ route('teacher.attendance.pdf', $query) }}" class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Download PDF</a>
                                <a href="{{ route('teacher.attendance.csv', $query) }}" class="px-3 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700">Download CSV</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 font-semibold text-gray-800">Daily Summary</div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-white">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Date</th>
                                                <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">P</th>
                                                <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">A</th>
                                                <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">L</th>
                                                <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">E</th>
                                                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">Note</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($dailyRows as $row)
                                                <tr class="{{ $row['is_holiday'] ? 'bg-yellow-50' : '' }}">
                                                    <td class="px-4 py-2 whitespace-nowrap">{{ $row['date']->format('D d M') }}</td>
                                                    @if ($row['is_holiday'])
                                                        <td colspan="4" class="px-4 py-2 text-center font-semibold text-yellow-800">H</td>
                                                        <td class="px-4 py-2 text-yellow-800">{{ $row['holiday_title'] }}</td>
                                                    @else
                                                        <td class="px-4 py-2 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_PRESENT] }}</td>
                                                        <td class="px-4 py-2 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_ABSENT] }}</td>
                                                        <td class="px-4 py-2 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_LATE] }}</td>
                                                        <td class="px-4 py-2 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_EXCUSED] }}</td>
                                                        <td class="px-4 py-2 text-gray-500">{{ $row['recorded_count'] > 0 ? $row['recorded_count'] . ' records' : 'Not recorded' }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 font-semibold text-gray-800">Legend and Totals</div>
                                <div class="p-4 space-y-3 text-sm text-gray-700">
                                    <p><strong>P</strong> Present, <strong>A</strong> Absent, <strong>L</strong> Late, <strong>E</strong> Excused, <strong>H</strong> Holiday.</p>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-md bg-green-50 p-3">Present: <strong>{{ $totals[\App\Models\Attendance::STATUS_PRESENT] }}</strong></div>
                                        <div class="rounded-md bg-red-50 p-3">Absent: <strong>{{ $totals[\App\Models\Attendance::STATUS_ABSENT] }}</strong></div>
                                        <div class="rounded-md bg-amber-50 p-3">Late: <strong>{{ $totals[\App\Models\Attendance::STATUS_LATE] }}</strong></div>
                                        <div class="rounded-md bg-blue-50 p-3">Excused: <strong>{{ $totals[\App\Models\Attendance::STATUS_EXCUSED] }}</strong></div>
                                    </div>
                                    @if ($holidayDates->count() > 0)
                                        <div>
                                            <p class="font-semibold mb-2">Calendar Holidays</p>
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach ($holidayDates as $date => $holiday)
                                                    <li>{{ \Carbon\Carbon::parse($date)->format('d M Y') }} — {{ $holiday['title'] }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="mb-5">
                            <h3 class="text-lg font-semibold text-gray-900">Student Register</h3>
                            <p class="text-sm text-gray-500">Blank cells mean attendance was not recorded for that teaching day.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="sticky left-0 bg-gray-50 z-10 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Learner</th>
                                        @foreach ($dates as $date)
                                            <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase whitespace-nowrap {{ $holidayDates->has($date->toDateString()) ? 'bg-yellow-50' : '' }}">
                                                {{ $date->format('d') }}<br>{{ $date->format('M') }}
                                            </th>
                                        @endforeach
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">P</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">A</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">L</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">E</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">%</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($studentRows as $row)
                                        <tr>
                                            <td class="sticky left-0 bg-white z-10 px-4 py-3 min-w-[220px]">
                                                <div class="font-semibold text-gray-900">{{ $row['student']->user->name ?? 'Unnamed Student' }}</div>
                                                <div class="text-xs text-gray-500">{{ $row['student']->admission_no }}</div>
                                            </td>
                                            @foreach ($dates as $date)
                                                @php($cell = $row['records'][$date->toDateString()] ?? ['code' => '', 'label' => ''])
                                                <td title="{{ $cell['label'] }}" class="px-2 py-3 text-center font-semibold {{ $cell['code'] === 'H' ? 'bg-yellow-50 text-yellow-800' : 'text-gray-800' }}">
                                                    {{ $cell['code'] }}
                                                </td>
                                            @endforeach
                                            <td class="px-3 py-3 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_PRESENT] }}</td>
                                            <td class="px-3 py-3 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_ABSENT] }}</td>
                                            <td class="px-3 py-3 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_LATE] }}</td>
                                            <td class="px-3 py-3 text-right">{{ $row['counts'][\App\Models\Attendance::STATUS_EXCUSED] }}</td>
                                            <td class="px-3 py-3 text-right font-semibold">{{ $row['attendance_percentage'] !== null ? number_format($row['attendance_percentage'], 1) . '%' : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-gray-600">
                        No class teacher class is assigned to your account for the active academic year.
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
