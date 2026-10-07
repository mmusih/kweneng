<!DOCTYPE html><html><head><meta charset="utf-8"><style>
@page { size: A3 landscape; margin: 8mm; }
body { font-family: DejaVu Sans,sans-serif; color: #17202a; font-size: 8px; }
h1 { font-size: 21px; margin: 0 0 4px; } .meta { font-size: 10px; margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; table-layout: auto; }
th, td { border: 0.5px solid #64748b; padding: 2px; vertical-align: top; word-wrap: break-word; }
th { background: #e2e8f0; text-align: center; } .name { width: 8%; font-size: 10px; text-align: left; }
.day-start { border-left: 1.5px solid #334155; } .entry + .entry { border-top: 0.5px dashed #94a3b8; margin-top: 2px; padding-top: 2px; }
thead { display: table-header-group; } tr { page-break-inside: avoid; } .legend { font-size: 9px; line-height: 1.6; }
</style></head><body>
<h1>{{ ucfirst($type === 'class' ? 'Class' : $type) }} timetable summary</h1>
<div class="meta">Kweneng International Secondary School · {{ $setting->name }} · {{ $setting->term_label }} · {{ $setting->is_published ? 'Published' : 'Working timetable' }}<br>{{ $excludedForms ? 'Excluded forms: '.implode(', ', $excludedForms) : 'All forms included' }} · Printed {{ now()->format('d M Y') }}</div>
@if($pages && $pages[0]['periods']->isNotEmpty())
@php($first = $pages[0])
<table><colgroup><col style="width: 90px">@foreach($first['days'] as $day)@foreach($first['periods'] as $period)<col>@endforeach @endforeach</colgroup><thead><tr><th class="name" rowspan="2">{{ ucfirst($type) }}</th>@foreach($first['days'] as $day)<th style="width: {{ 92 / count($first['days']) }}%" colspan="{{ count($first['periods']) }}" class="day-start">Day {{ $day }}</th>@endforeach</tr>
<tr>@foreach($first['days'] as $day)@foreach($first['periods'] as $period)<th class="{{ $loop->first ? 'day-start' : '' }}">{{ $period->period_number }}</th>@endforeach @endforeach</tr></thead><tbody>
@foreach($pages as $page)<tr><th class="name">{{ $page['entityName'] }}</th>
@foreach($page['days'] as $day)@foreach(\App\Support\Timetable\PrintCells::row($page['periods'], $page['cells'][$day] ?? [], $page['breaks']) as $cell)<td colspan="{{ $cell['span'] }}" class="{{ $loop->first ? 'day-start' : '' }}">
@foreach($cell['entries'] as $entry)<div class="entry"><strong>{{ $entry['subject'] }}</strong>
@if($type !== 'class')<div>{{ preg_replace('/Form\s*/i', 'F', $entry['classes']) }}</div>@endif
@if($type !== 'teacher')<div>{{ $entry['teacher_codes'] }}</div>@endif
@if($type !== 'room')<div>{{ $entry['room_code'] }}</div>@endif
</div>@endforeach</td>@endforeach @endforeach</tr>@endforeach
</tbody></table>
<p class="legend"><strong>Period times:</strong> @foreach($first['periods'] as $period){{ $period->period_number }}: {{ substr($period->start_time,0,5) }}-{{ substr($period->end_time,0,5) }}; @endforeach<br><strong>Breaks:</strong> @foreach($first['breaks'] as $break){{ $break->name }} after period {{ $break->after_period }} ({{ substr($break->start_time,0,5) }}-{{ substr($break->end_time,0,5) }}); @endforeach</p>
@if($type !== 'teacher')<p class="legend"><strong>Teachers:</strong> @foreach($teacherLegend as $teacher)T{{ $teacher->id }} = {{ $teacher->user?->name }}; @endforeach</p>@endif
@if($type !== 'room')<p class="legend"><strong>Rooms:</strong> @foreach($roomLegend as $room)R{{ $room->id }} = {{ $room->name }}; @endforeach</p>@endif
@else<p>No timetables match this selection.</p>@endif
</body></html>
