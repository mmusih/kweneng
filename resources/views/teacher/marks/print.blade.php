<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Results – {{ $class->name }} – {{ $term->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #111;
            padding: 32px;
        }

        .header {
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #1e1e2e;
        }

        .header p {
            font-size: 13px;
            color: #555;
            margin-top: 4px;
        }

        .meta {
            display: flex;
            gap: 32px;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .meta span {
            color: #555;
        }

        .meta strong {
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead tr {
            background: #f3f4f6;
        }

        th {
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #d1d5db;
            white-space: nowrap;
        }

        th.subject-header {
            text-align: center;
        }

        td {
            padding: 7px 10px;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        td.score {
            text-align: center;
        }

        td.grade {
            text-align: center;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .no-mark {
            color: #aaa;
        }

        .subject-group th {
            background: #e0e7ff;
            color: #3730a3;
            text-align: center;
            font-size: 11px;
            padding: 4px 6px;
        }

        .footer {
            margin-top: 32px;
            font-size: 11px;
            color: #888;
        }

        @media print {
            body {
                padding: 16px;
            }

            @page {
                margin: 1.5cm;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Class Results Sheet</h1>
        <p>{{ $class->name }} &mdash; {{ $term->name }} &mdash; {{ $class->academicYear->year_name ?? '' }}</p>
        <p style="margin-top: 6px; font-size: 13px; color: #555;">
            <strong style="color: #111;">Subjects:</strong>
            {{ $subjects->pluck('name')->join(', ') }}
        </p>
    </div>

    <div class="meta">
        <div><span>Class: </span><strong>{{ $class->name }}</strong></div>
        <div><span>Term: </span><strong>{{ $term->name }}</strong></div>
        <div><span>Students: </span><strong>{{ $students->count() }}</strong></div>
        <div><span>Printed: </span><strong>{{ now()->format('d M Y, H:i') }}</strong></div>
    </div>

    <table>
        <thead>
            {{-- Subject group header --}}
            <tr class="subject-group">
                <th colspan="2"></th>
                @foreach ($subjects as $subject)
                    <th colspan="3">{{ $subject->name }}</th>
                @endforeach
            </tr>
            {{-- Column headers --}}
            <tr>
                <th>#</th>
                <th>Student</th>
                @foreach ($subjects as $subject)
                    <th class="subject-header">Midterm</th>
                    <th class="subject-header">Endterm</th>
                    <th class="subject-header">Grade</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $i => $student)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $student->user->name ?? 'N/A' }}
                    </td>
                    @foreach ($subjects as $subject)
                        @php
                            $allowedSubjects = $allowedSubjectIdsByStudent->get($student->id, collect());
                            $isAssignedForSubject = $allowedSubjects->contains((int) $subject->id);
                            $mark = $isAssignedForSubject ? ($results[$student->id][$subject->id] ?? null) : null;
                        @endphp
                        @if (!$isAssignedForSubject)
                            <td class="score no-mark">N/A</td>
                            <td class="score no-mark">N/A</td>
                            <td class="grade no-mark">N/A</td>
                        @else
                            <td class="score">
                                {{ $mark?->midterm_score !== null ? number_format($mark->midterm_score, 1) : '—' }}
                            </td>
                            <td class="score">
                                {{ $mark?->endterm_score !== null ? number_format($mark->endterm_score, 1) : '—' }}
                            </td>
                            <td class="grade">
                                <span class="{{ $mark?->grade ? '' : 'no-mark' }}">
                                    {{ $mark?->grade ?? '—' }}
                                </span>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated by {{ config('app.name') }} &bull; {{ now()->format('d M Y H:i') }}
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>

</body>

</html>
