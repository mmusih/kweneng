<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Register</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 18px; margin: 0; }
        .meta { font-size: 10px; margin-top: 3px; }
        .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #777; padding: 3px; }
        th { background: #eee; }
        .center { text-align: center; }
        .right { text-align: right; }
        .holiday { background: #fff2cc; }
        .summary td { border: 1px solid #aaa; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Register</h1>
        <div class="meta">Class: {{ $selectedClass?->name }} · Academic Year: {{ $activeAcademicYear?->year_name }} · Term: {{ $activeTerm?->name }}</div>
        <div class="meta">Period: {{ $fromDate->format('d M Y') }} to {{ $toDate->format('d M Y') }}</div>
        <div class="meta">Legend: P Present · A Absent · L Late · E Excused · H Holiday</div>
    </div>

    <table class="summary" style="margin-bottom: 8px;">
        <tr>
            <td><strong>Learners:</strong> {{ $students->count() }}</td>
            <td><strong>Recorded Days:</strong> {{ $recordedTeachingDates->count() }}</td>
            <td><strong>Holidays:</strong> {{ $holidayDates->count() }}</td>
            <td><strong>Unmarked Days:</strong> {{ $unmarkedTeachingDays }}</td>
            <td><strong>Attendance Rate:</strong> {{ $classAttendancePercentage !== null ? number_format($classAttendancePercentage, 1) . '%' : 'N/A' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">No.</th>
                <th style="width: 70px;">Adm.</th>
                <th style="width: 150px;">Learner</th>
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
        <p><strong>Calendar Holidays:</strong>
            @foreach ($holidayDates as $date => $holiday)
                {{ \Carbon\Carbon::parse($date)->format('d M Y') }} - {{ $holiday['title'] }}@if(!$loop->last); @endif
            @endforeach
        </p>
    @endif
</body>
</html>
