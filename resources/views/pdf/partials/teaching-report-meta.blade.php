<div class="meta">{{ $source === 'published' ? 'Current published timetable' : 'Latest working timetable (may be unpublished)' }} · Generated {{ now()->format('d M Y H:i') }}<br>
Periods per cycle. Single = 1; double = 2. Shared options, splits and joint classes count once per occupied period. Unplaced lessons excluded.<br>
@foreach($summary['schedules'] as $schedule){{ $schedule['label'] }}: {{ $schedule['name'] }} · {{ $schedule['term_label'] }} · Revision {{ $schedule['revision'] }} · {{ $schedule['cycle_length'] }}-day cycle<br>@endforeach
@if(!$summary['schedules'])No timetable exists for the selected source and year.<br>@endif
Teacher totals count each occupied position once, including when subjects alternate in that position.</div>

<p>{{ $excludedForms ? 'Excluded forms: '.implode(', ', $excludedForms) : 'All forms included' }}</p>
