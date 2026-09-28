<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:10px}h1{margin:0;color:#124e66}.meta{margin:4px 0 16px;color:#64748b}table{width:100%;border-collapse:collapse}th,td{border:1px solid #cbd5e1;padding:7px;vertical-align:top}th{background:#124e66;color:white;text-align:left}.total{font-weight:bold;background:#f1f5f9}.right{text-align:right}.subject{display:block;margin-bottom:3px}
</style></head><body>
<h1>Teacher Load Summary</h1><div class="meta">{{ $summary['academic_year'] }} · Generated {{ now()->format('d M Y H:i') }}</div>
<table><thead><tr><th>Teacher</th>@foreach($summary['schedules'] as $schedule)<th>{{ $schedule['label'] }} timetable</th>@endforeach<th class="right">All periods</th></tr></thead><tbody>
@foreach($summary['teachers'] as $teacher)<tr><td><strong>{{ $teacher['teacher_name'] }}</strong></td>@foreach($teacher['schedules'] as $load)<td>@foreach($load['subjects'] as $subject)<span class="subject">{{ $subject['subject'] }}: <strong>{{ $subject['periods'] }}</strong></span>@endforeach<div class="total">Total: {{ $load['total'] }}</div></td>@endforeach<td class="right"><strong>{{ $teacher['grand_total'] }}</strong></td></tr>@endforeach
</tbody></table></body></html>
