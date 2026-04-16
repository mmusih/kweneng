<style>
    @page {
        size: A4 landscape;
        margin: 9mm;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9px;
        color: #111;
        margin: 0;
        padding: 0;
        line-height: 1.3;
    }

    .page {
        width: 100%;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }

    .header-table td {
        border: none;
        vertical-align: top;
        padding: 0;
    }

    .logo-cell {
        width: 75px;
    }

    .logo {
        width: 60px;
        height: auto;
    }

    .title-cell {
        text-align: center;
        padding-top: 2px;
    }

    .school-name {
        font-size: 17px;
        font-weight: bold;
        color: #0f172a;
        letter-spacing: 0.2px;
    }

    .report-title {
        font-size: 11px;
        font-weight: bold;
        margin-top: 3px;
        text-transform: uppercase;
        color: #334155;
    }

    .info-line {
        margin-top: 8px;
        font-size: 9px;
        padding: 3px 0;
        display: flex;
        justify-content: space-between;
        white-space: nowrap;
        gap: 10px;
    }

    .info-item {
        display: inline-block;
    }

    .label {
        font-weight: bold;
        color: #1e293b;
    }

    .section-title {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin: 10px 0 4px 0;
        color: #0f172a;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    th,
    td {
        border: 1px solid #cbd5e1;
        padding: 4px 5px;
        vertical-align: middle;
    }

    th {
        background: #f1f5f9;
        text-align: center;
        font-size: 8.3px;
        font-weight: bold;
        color: #0f172a;
    }

    td {
        font-size: 8.3px;
        color: #111827;
    }

    .subject-col {
        width: 24%;
    }

    .score-col {
        width: 10%;
        text-align: center;
    }

    .comment-col {
        width: 38%;
    }

    .teacher-col {
        width: 18%;
    }

    .head-comment {
        margin-top: 12px;
    }

    .head-comment-title {
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .head-comment-line {
        border-bottom: 1px solid #64748b;
        min-height: 18px;
        padding: 2px 0 4px 0;
        font-size: 9px;
    }

    .notes {
        margin-top: 8px;
        font-size: 8.5px;
        color: #374151;
    }

    .notes div {
        margin-top: 3px;
    }

    .grade-scale {
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px solid #cbd5e1;
        font-size: 8px;
        color: #374151;
    }

    .grade-scale strong {
        color: #111827;
    }

    .grade-scale span {
        display: inline-block;
        margin-right: 10px;
        margin-top: 2px;
    }

    .muted {
        color: #6b7280;
    }
</style>

<div class="page">

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo" alt="School Logo">
                @endif
            </td>
            <td class="title-cell">
                <div class="school-name">{{ $schoolName }}</div>
                <div class="report-title">{{ $term->report_title ?: 'End of Term Report' }}</div>
            </td>
        </tr>
    </table>

    <div class="info-line">
        <div class="info-item">
            <span class="label">Student Name:</span> {{ $student->user->name }}
        </div>
        <div class="info-item">
            <span class="label">Form:</span> {{ $student->currentClass->name ?? 'N/A' }}
        </div>
        <div class="info-item">
            <span class="label">Academic Year:</span> {{ $academicYear->year_name }}
        </div>
        <div class="info-item">
            <span class="label">Term:</span> {{ $term->name }}
        </div>
    </div>

    <div class="section-title">Subject Performance</div>

    <table>
        <thead>
            <tr>
                <th class="subject-col">Subject</th>
                <th class="score-col">Midterm</th>
                <th class="score-col">Endterm</th>
                <th class="comment-col">Teacher Comment</th>
                <th class="teacher-col">Teacher</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($subjects as $row)
                <tr>
                    <td>{{ $row['subject_name'] }}</td>

                    <td class="score-col">
                        @if ($row['midterm_score'] !== null)
                            {{ number_format($row['midterm_score'], 0) }}{{ $row['midterm_grade'] }}
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td class="score-col">
                        @if ($row['endterm_score'] !== null)
                            {{ number_format($row['endterm_score'], 0) }}{{ $row['endterm_grade'] }}
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td>{{ $row['teacher_comment'] ?? '—' }}</td>

                    <td>{{ $row['teacher_name'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">No subject marks available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Academic Summary</div>

    <div class="info-line">
        <div class="info-item">
            <span class="label">Endterm Average:</span>
            {{ $endtermAverage !== null ? number_format($endtermAverage, 0) . '%' : 'N/A' }}
        </div>

        <div class="info-item">
            <span class="label">Class Position:</span>
            @if ($endtermRanking && $endtermRanking['position'] !== null)
                {{ $endtermRanking['position'] }}/{{ $endtermRanking['class_size'] }}
            @else
                N/A
            @endif
        </div>

        <div class="info-item">
            <span class="label">Class Teacher:</span> {{ $classTeacherName }}
        </div>

        <div class="info-item">
            <span class="label">Attendance:</span>
            {{ $attendanceSummary['label'] ?? ($attendanceSummary['rate'] !== null ? number_format($attendanceSummary['rate'], 1) . '%' : 'N/A') }}
        </div>

        <div class="info-item">
            <span class="label">Punctuality:</span> {{ $punctualitySummary['label'] }}
        </div>

        <div class="info-item">
            <span class="label">Behaviour:</span> {{ $behaviourSummary['label'] }}
        </div>
    </div>

    <div class="head-comment">
        <div class="head-comment-title">Headmaster Comment</div>
        <div class="head-comment-line">
            {{ $headmasterComment?->comment ?? '' }}
        </div>
    </div>

    <div class="notes">
        @if ($term->report_footer_note)
            <div>{{ $term->report_footer_note }}</div>
        @endif

        @if ($term->report_office_note)
            <div>{{ $term->report_office_note }}</div>
        @endif

        @if ($term->report_extra_note)
            <div>{{ $term->report_extra_note }}</div>
        @endif
    </div>

    <div class="grade-scale">
        <strong>Grade Scale:</strong>
        <span><strong>A*</strong> 90–100</span>
        <span><strong>A</strong> 80–89</span>
        <span><strong>B</strong> 70–79</span>
        <span><strong>C</strong> 60–69</span>
        <span><strong>D</strong> 50–59</span>
        <span><strong>E</strong> 40–49</span>
        <span><strong>F</strong> 35–39</span>
        <span><strong>G</strong> Below 35</span>
    </div>

</div>
