<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Report Card</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .logo {
            width: 62px;
            height: auto;
            margin-bottom: 4px;
        }

        .school-name {
            font-size: 16px;
            font-weight: bold;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            margin-top: 2px;
        }

        .student-meta {
            margin-top: 8px;
            margin-bottom: 8px;
            font-size: 10px;
            line-height: 1.7;
        }

        .student-meta span {
            margin-right: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px 4px;
            vertical-align: middle;
        }

        th {
            background: #eeeeee;
            text-align: center;
            font-size: 9px;
        }

        td {
            font-size: 9px;
        }

        .subject-col {
            width: 23%;
        }

        .score-col {
            width: 11%;
            text-align: center;
        }

        .comment-col {
            width: 31%;
        }

        .teacher-col {
            width: 13%;
        }

        .below-table {
            margin-top: 8px;
            font-size: 10px;
            line-height: 1.8;
        }

        .comment-box {
            margin-top: 8px;
            border: 1px solid #000;
            padding: 8px;
            min-height: 60px;
        }

        .summary-line {
            margin-top: 8px;
            font-size: 10px;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 9px;
        }

        .grade-scale {
            margin-top: 8px;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 6px;
        }

        .grade-scale span {
            margin-right: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        @if (file_exists($logoPath))
            <img src="{{ $logoPath }}" class="logo" alt="School Logo">
        @endif

        <div class="school-name">{{ $schoolName }}</div>
        <div class="report-title">{{ $term->report_title ?: 'End of Term Report' }}</div>
    </div>

    <div class="student-meta">
        <span><strong>Student Name:</strong> {{ $student->user->name }}</span>
        <span><strong>Form:</strong> {{ $student->currentClass->name ?? 'N/A' }}</span>
        <span><strong>Year:</strong> {{ $academicYear->year_name }}</span>
        <span><strong>Term:</strong> {{ $term->name }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="subject-col">Subject</th>
                <th class="score-col">Mid-term</th>
                <th class="score-col">Exam</th>
                <th class="comment-col">Teacher's Comments</th>
                <th class="teacher-col">Teacher</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subjects as $row)
                <tr>
                    <td>{{ $row['subject_name'] }}</td>
                    <td style="text-align:center;">
                        @if ($row['midterm_score'] !== null)
                            {{ number_format($row['midterm_score'], 0) }}{{ $row['midterm_grade'] }}
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if ($row['endterm_score'] !== null)
                            {{ number_format($row['endterm_score'], 0) }}{{ $row['endterm_grade'] }}
                        @endif
                    </td>
                    <td>{{ $row['teacher_comment'] ?? '' }}</td>
                    <td>{{ $row['teacher_name'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="below-table">
        <strong>Class Teacher:</strong> {{ $classTeacherName }}
        &nbsp;&nbsp;&nbsp;
        <strong>Punctuality:</strong> {{ $punctualitySummary['label'] }}
        &nbsp;&nbsp;&nbsp;
        <strong>Attendance:</strong> {{ $attendanceSummary['display'] }}
        &nbsp;&nbsp;&nbsp;
        <strong>Behaviour:</strong> {{ $behaviourSummary['label'] }}
    </div>

    <div class="comment-box">
        <strong>Head’s Comment:</strong><br><br>
        {{ $headmasterComment?->comment ?? '' }}
    </div>

    <div class="summary-line">
        <strong>Average:</strong> {{ $endtermAverage !== null ? number_format($endtermAverage, 0) . '%' : 'N/A' }}
        &nbsp;&nbsp;&nbsp;
        <strong>Class Position:</strong>
        @if ($endtermRanking && $endtermRanking['position'] !== null)
            {{ $endtermRanking['position'] }}/{{ $endtermRanking['class_size'] }}
        @else
            N/A
        @endif
    </div>

    @if ($term->report_footer_note)
        <div class="footer-note">{{ $term->report_footer_note }}</div>
    @endif

    @if ($term->report_office_note)
        <div class="footer-note">{{ $term->report_office_note }}</div>
    @endif

    @if ($term->report_extra_note)
        <div class="footer-note">{{ $term->report_extra_note }}</div>
    @endif

    <div class="grade-scale">
        <strong>Grade Scale:</strong>
        <span><strong>A*</strong> 90 to 100</span>
        <span><strong>A</strong> 80 to 89</span>
        <span><strong>B</strong> 70 to 79</span>
        <span><strong>C</strong> 60 to 69</span>
        <span><strong>D</strong> 50 to 59</span>
        <span><strong>E</strong> 40 to 49</span>
        <span><strong>F</strong> 35 to 39</span>
        <span><strong>G</strong> &lt; 35</span>
    </div>

</body>

</html>
