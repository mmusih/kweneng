<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Exam Summary Sheet</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            margin: 0;
            color: #000;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            width: 48px;
            height: auto;
        }

        .title-block {
            text-align: center;
        }

        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .sheet-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 1px;
        }

        .meta {
            font-size: 8.5px;
        }

        .right-meta {
            text-align: right;
            font-size: 8.5px;
            line-height: 1.35;
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
            padding: 2px 1px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.05;
            overflow: hidden;
        }

        .summary-table th {
            background: #000;
            color: #fff;
            font-weight: bold;
        }

        .left {
            text-align: left !important;
        }

        .nowrap {
            white-space: nowrap;
        }

        /* tighter utility widths */
        .index-col {
            width: 10px;
        }

        .surname-col {
            width: 118px;
        }

        .name-col {
            width: 138px;
        }

        .class-col {
            width: 24px;
        }

        .subject-col {
            width: 20px;
        }

        .avg-col {
            width: 24px;
        }

        .total-col {
            width: 28px;
        }

        .pos-col {
            width: 16px;
        }

        .alt {
            background: #e0e0e0;
        }

        .legend {
            margin-top: 4px;
            font-size: 7.8px;
            border: 1px solid #000;
            padding: 4px 6px;
        }

        .legend span {
            margin-right: 8px;
        }
    </style>
</head>

<body>

    @php
        $classLabel = preg_replace('/^Form\s+/i', '', $summary['class']->name);
    @endphp

    <table class="header-table">
        <tr>
            <td style="width: 55px;">
                @if (!empty($logoPath) && is_file($logoPath))
                    <img src="{{ $logoPath }}" class="logo" alt="School Logo">
                @endif
            </td>

            <td class="title-block">
                <div class="school-name">{{ $schoolName }}</div>
                <div class="sheet-title">
                    {{ $summary['class']->name }} - {{ ucfirst($summary['exam_type']) }} Summary
                </div>
                <div class="meta">{{ $term->name }} | {{ $academicYear->year_name }}</div>
            </td>

            <td class="right-meta" style="width: 165px;">
                <div><strong>Teacher:</strong> {{ $summary['class_teacher_name'] }}</div>
                <div><strong>Class Avg:</strong>
                    {{ $summary['class_average'] !== null ? (int) ceil($summary['class_average']) : '-' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <colgroup>
            <col class="index-col">
            <col class="surname-col">
            <col class="name-col">
            <col class="class-col">

            @foreach ($summary['subjects'] as $subject)
                <col class="subject-col">
            @endforeach

            <col class="avg-col">
            <col class="total-col">
            <col class="pos-col">
        </colgroup>

        <thead>
            <tr>
                <th>#</th>
                <th class="left">Surname</th>
                <th class="left">Name</th>
                <th>Cls</th>

                @foreach ($summary['subjects'] as $subject)
                    <th>{{ $subject['code'] }}</th>
                @endforeach

                <th>AV</th>
                <th>TOT</th>
                <th>PS</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($summary['rows'] as $i => $row)
                @php
                    $parts = preg_split('/\s+/', trim($row['student_name']), 2);
                    $surname = $parts[0] ?? '';
                    $name = $parts[1] ?? '';
                @endphp

                <tr class="{{ $i % 2 ? 'alt' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td class="left nowrap">{{ $surname }}</td>
                    <td class="left nowrap">{{ $name }}</td>
                    <td>{{ $classLabel }}</td>

                    @foreach ($summary['subjects'] as $subject)
                        <td>{{ $row['scores'][$subject['id']]['display'] ?? '' }}</td>
                    @endforeach

                    <td>{{ $row['average'] !== null ? (int) ceil($row['average']) : '' }}</td>
                    <td>{{ $row['total'] !== null ? (int) ceil($row['total']) : '' }}</td>
                    <td>{{ $row['position'] ?? '' }}</td>
                </tr>
            @endforeach

            <tr>
                <td colspan="4"><strong>Average</strong></td>

                @foreach ($summary['subjects'] as $subject)
                    @php
                        $avg = collect($summary['subject_averages'])->firstWhere('subject_id', $subject['id']);
                    @endphp
                    <td>
                        @if (!empty($avg['average']))
                            {{ (int) ceil($avg['average']) }}{{ $avg['grade'] ?? '' }}
                        @endif
                    </td>
                @endforeach

                <td>{{ $summary['class_average'] !== null ? (int) ceil($summary['class_average']) : '' }}</td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="legend">
        @foreach ($summary['grade_scale'] as $grade => $range)
            <span><strong>{{ $grade }}</strong>: {{ $range }}</span>
        @endforeach
    </div>

</body>

</html>
