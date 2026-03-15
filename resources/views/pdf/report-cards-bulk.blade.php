<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Bulk Report Cards</title>
    <style>
        .page-break {
            page-break-after: always;
        }

        .page-break:last-child {
            page-break-after: auto;
        }
    </style>
</head>

<body>
    @foreach ($reports as $report)
        <div class="page-break">
            @include(
                'pdf.partials.report-card-content',
                array_merge($report, [
                    'schoolName' => $schoolName,
                    'logoPath' => $logoPath,
                ]))
        </div>
    @endforeach
</body>

</html>
