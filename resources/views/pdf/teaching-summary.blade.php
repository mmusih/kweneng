<!doctype html><html><head><meta charset="utf-8">
@include('admin.timetable.partials.report-style')
<style>@page { size: A3 landscape; margin: 12mm; } body { font-family: DejaVu Sans,sans-serif; color: #1f2937; font-size: 10px; } h1 { font-size: 24px; color: #124e66; } .meta { color: #475569; line-height: 1.5; } .teaching-report { font-size: 10px; } .teaching-report th,.teaching-report td { padding: 7px; } .report-section h2 { page-break-after: avoid; } </style>
</head><body><h1>Teaching summary · {{ $summary['academic_year'] }}</h1>
@include('pdf.partials.teaching-report-meta')
@include('admin.timetable.partials.report-table', ['report' => 'teaching-summary', 'pdf' => true])
</body></html>
