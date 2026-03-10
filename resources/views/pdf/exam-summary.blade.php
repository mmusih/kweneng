<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Exam Summary Sheet</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 8mm 8mm 8mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            width: 52px;
            height: auto;
        }

        .title-block {
            text-align: center;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sheet-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .meta {
            font-size: 9px;
        }

        .right-meta {
            text-align: right;
            font-size: 9px;
            line-height: 1.5;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .summary-table th.name-col,
        .summary-table td.name-col {
            text-align: left;
            width: 150px;
        }

        .summary-table th.small-col,
        .summary-table td.small-col {
            width: 38px;
        }

        .summary-table th.medium-col,
        .summary-table td.medium-col {
            width: 48px;
        }

        .subject-avg-row {
            font-weight: bold;
        }

        .legend {
            margin-top: 6px;
            font-size: 8.5px;
            border: 1px solid #000;
            padding: 5px 7px;
        }

        .legend-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .legend-items span {
            margin-right: 10px;
        }

        .note {
            margin-top: 4px;
            font-size: 8px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td style="width: 65px;">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo" alt="School Logo">
                @endif
            </td>

            <td class="title-block">
                <div class="school-name">{{ $schoolName }}</div>
                <div class="sheet-title">{{ $summary['class']->name }} - {{ ucfirst($summary['exam_type']) }} Summary
                    Sheet</div>
                <div class="meta">{{ $term->name }} | {{ $academicYear->year_name }}</div>
            </td>

            <td class="right-meta" style="width: 180px;">
                <div><strong>Class Teacher:</strong> {{ $summary['class_teacher_name'] }}</div>
                <div><strong>Class Average:</strong>
                    {{ $summary['class_average'] !== null ? number_format($summary['class_average'], 2) : 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <thead>
            <tr>
                <th class="name-col">Student Name</th>
                @foreach ($summary['subjects'] as $subject)
                    <th class="small-col">{{ $subject['code'] }}</th>
                @endforeach
                <th class="medium-col">TOT</th>
                <th class="medium-col">AVG</th>
                <th class="small-col">POS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['rows'] as $row)
                <tr>
                    <td class="name-col">{{ $row['student_name'] }}</td>

                    @foreach ($summary['subjects'] as $subject)
                        <td class="small-col">
                            {{ $row['scores'][$subject['id']]['display'] ?? '' }}
                        </td>
                    @endforeach

                    <td class="medium-col">
                        {{ $row['total'] !== null ? number_format($row['total'], 2) : '' }}
                    </td>

                    <td class="medium-col">
                        @if ($row['average'] !== null)
                            {{ number_format($row['average'], 2) }}{{ $row['grade'] ?? '' }}
                        @endif
                    </td>

                    <td class="small-col">{{ $row['position'] ?? '' }}</td>
                </tr>
            @endforeach

            <tr class="subject-avg-row">
                <td class="name-col">Subject Avg</td>

                @foreach ($summary['subject_averages'] as $subjectAverage)
                    <td class="small-col">{{ $subjectAverage['display'] }}</td>
                @endforeach

                <td class="medium-col"></td>
                <td class="medium-col">
                    {{ $summary['class_average'] !== null ? number_format($summary['class_average'], 2) : '' }}
                </td>
                <td class="small-col"></td>
            </tr>
        </tbody>
    </table>

    <div class="legend">
        <div class="legend-title">Grade Key</div>
        <div class="legend-items">
            @foreach ($summary['grade_scale'] as $grade => $range)
                <span><strong>{{ $grade }}</strong>: {{ $range }}</span>
            @endforeach
        </div>
    </div>

    <div class="note">
        Blank cells indicate subjects not taken by that student or no recorded score for the selected exam.
    </div>

</body>

</html>
