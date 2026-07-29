<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Register - {{ $selectedClass?->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111827; margin: 18px; }
        h1, h2, h3 { margin: 0; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .meta { margin-top: 6px; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 4px; vertical-align: middle; }
        th { background: #f3f4f6; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .holiday { background: #fef3c7; }
        .small { font-size: 10px; color: #4b5563; }
        .summary { margin: 12px 0; display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; }
        .summary div { border: 1px solid #d1d5db; padding: 6px; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom: 12px; padding: 8px 12px;">Print</button>

    <div class="header">
        <div>
            <h1>Attendance Register</h1>
            <div class="meta">{{ $selectedClass?->name }} · {{ $activeAcademicYear?->year_name }} · {{ $activeTerm?->name }}</div>
            <div class="meta">{{ $fromDate->format('d M Y') }} to {{ $toDate->format('d M Y') }}</div>
        </div>
        <div class="right">
            <h3>Legend</h3>
            <div class="small">P Present · A Absent · L Late · E Excused · H Holiday</div>
        </div>
    </div>

    <div class="summary">
        <div><strong>Learners</strong><br>{{ $students->count() }}</div>
        <div><strong>Recorded Days</strong><br>{{ $recordedTeachingDates->count() }}</div>
        <div><strong>Holidays</strong><br>{{ $holidayDates->count() }}</div>
        <div><strong>Unmarked Days</strong><br>{{ $unmarkedTeachingDays }}</div>
        <div><strong>Present + Late</strong><br>{{ $totals[\App\Models\Attendance::STATUS_PRESENT] + $totals[\App\Models\Attendance::STATUS_LATE] }}</div>
        <div><strong>Attendance Rate</strong><br>{{ $classAttendancePercentage !== null ? number_format($classAttendancePercentage, 1) . '%' : 'N/A' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 36px;">No.</th>
                <th style="width: 90px;">Admission</th>
                <th style="width: 190px;">Learner</th>
                @foreach ($dates as $date)
                    <th class="center {{ $holidayDates->has($date->toDateString()) ? 'holiday' : '' }}">{{ $date->format('d/m') }}</th>
                @endforeach
                <th class="center">P</th>
                <th class="center">A</th>
                <th class="center">L</th>
                <th class="center">E</th>
                <th class="center">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($studentRows as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['student']->admission_no }}</td>
                    <td>{{ $row['student']->user->name ?? 'Unnamed Student' }}</td>
                    @foreach ($dates as $date)
                        @php($cell = $row['records'][$date->toDateString()] ?? ['code' => ''])
                        <td class="center {{ $cell['code'] === 'H' ? 'holiday' : '' }}">{{ $cell['code'] }}</td>
                    @endforeach
                    <td class="center">{{ $row['counts'][\App\Models\Attendance::STATUS_PRESENT] }}</td>
                    <td class="center">{{ $row['counts'][\App\Models\Attendance::STATUS_ABSENT] }}</td>
                    <td class="center">{{ $row['counts'][\App\Models\Attendance::STATUS_LATE] }}</td>
                    <td class="center">{{ $row['counts'][\App\Models\Attendance::STATUS_EXCUSED] }}</td>
                    <td class="center">{{ $row['attendance_percentage'] !== null ? number_format($row['attendance_percentage'], 1) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($holidayDates->count() > 0)
        <h3 style="margin-top: 14px;">Calendar Holidays</h3>
        <table style="margin-top: 6px; width: 60%;">
            <thead><tr><th>Date</th><th>Holiday</th></tr></thead>
            <tbody>
                @foreach ($holidayDates as $date => $holiday)
                    <tr><td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td><td>{{ $holiday['title'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
