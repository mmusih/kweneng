<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
@page { margin: 20pt 22pt; }
body { font-family: DejaVu Sans, sans-serif; font-size: {{ $fontSize }}pt; color: #17384b; margin: 0; line-height: 1.1; }
h1 { font-size: 17pt; margin: 0 0 4pt; }
.meta { color: #526675; margin-bottom: 10pt; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th { background: #124e66; color: white; padding: 7pt 3pt; }
td { vertical-align: top; border: 1px solid #ccd8df; padding: 3pt; }
.block { padding: {{ $fontSize > 7 ? 3 : 2 }}pt 3pt; border-bottom: 1px solid #dce4e9; }
.time { color: #526675; font-size: {{ $fontSize - 1 }}pt; }
.title { font-weight: bold; margin: 1pt 0; }
.detail { font-size: {{ $fontSize - 1 }}pt; color: #526675; overflow-wrap: break-word; }
.event { background: #f0f5f7; }
.footer { margin-top: 8pt; font-size: 7pt; color: #526675; }
</style></head><body>
<h1>{{ $schedule['template']['schedule_label'] }} timetable</h1>
<div class="meta">{{ $student->user?->name }} | {{ $student->admission_no }} | {{ $student->currentClass?->name }}<br>
{{ $schedule['template']['name'] }} | {{ $schedule['template']['academic_year'] }} | {{ count($schedule['days']) }} days</div>
<table><thead><tr>@foreach($schedule['days'] as $day)<th>{{ $day['name'] }}</th>@endforeach</tr></thead>
<tbody><tr>@foreach($schedule['days'] as $day)<td>
@forelse($day['blocks'] as $block)
<div class="block {{ $block['kind'] === 'lesson' ? '' : 'event' }}">
<div class="time">{{ $block['start_time'] }} - {{ $block['end_time'] }}</div>
<div class="title">{{ $block['title'] }}</div>
@if($block['room'] ?? null)<div class="detail">Room: {{ $block['room'] }}</div>@endif
@if($block['teacher'] ?? null)<div class="detail">{{ $block['teacher'] }}</div>@endif
@if($block['group'] ?? null)<div class="detail">{{ $block['group'] }}</div>@endif
</div>
@empty<div class="block">No scheduled activities</div>@endforelse
</td>@endforeach</tr></tbody></table>
<div class="footer">Published student timetable. Times are school local time. Download a new copy when the school changes its timetable.</div>
</body></html>
