@foreach($summary['schedules'] as $schedule)
<section class="report-section">
<h2>{{ $schedule['type'] === 'day' ? 'Daytime teaching' : 'Afternoon / study teaching' }}</h2>
<p class="report-caption">{{ $schedule['name'] }} · {{ $schedule['cycle_length'] }}-day cycle · Scheduled periods</p>
<div class="overflow-x-auto"><table class="teaching-report"><thead><tr>
<th>Teacher</th><th>{{ $report === 'teacher-loads' ? 'Subjects taught' : 'Subject' }}</th>
@if($report === 'teaching-summary')<th>Classes</th><th>Groups / options</th>@endif
@foreach(range(1, max(1, $schedule['cycle_length'])) as $day)<th class="number">Day {{ $day }}</th>@endforeach<th class="number">Cycle total</th>
</tr></thead><tbody>
@php($rowCount = 0)
@foreach($summary['teachers'] as $teacher)
@php($load = collect($teacher['schedules'])->firstWhere('setting_id', $schedule['id']))
@if($report === 'teacher-loads')
@php($rowCount++)
<tr><th scope="row">@if(!($pdf ?? false))<a href="{{ route($routePrefix.'.timetable.teaching-summary', array_merge($reportFilters, ['teacher_id' => $teacher['teacher_id']])) }}">{{ $teacher['teacher_name'] }}</a>@else{{ $teacher['teacher_name'] }}@endif</th>
<td>@forelse(collect($load['subjects'])->where('scheduled_periods', '>', 0) as $subject)<div>{{ $subject['subject'] }} <strong>({{ $subject['scheduled_periods'] }})</strong></div>@empty<span class="muted">No scheduled lessons</span>@endforelse</td>
@foreach($load['days'] as $count)<td class="number {{ $count ? '' : 'muted' }}">{{ $count ?: '–' }}</td>@endforeach<td class="number total">{{ $load['scheduled_total'] }}</td></tr>
@else
@foreach(collect($load['subjects'])->where('scheduled_periods', '>', 0) as $subject)
@php($rowCount++)
<tr><th scope="row">{{ $loop->first ? $teacher['teacher_name'] : '' }}</th><td>{{ $subject['subject'] }}</td><td>{{ implode(', ', $subject['classes']) ?: 'No class linked' }}</td><td>{{ implode('; ', $subject['groups']) ?: 'Whole class' }}</td>
@foreach($subject['days'] as $count)<td class="number {{ $count ? '' : 'muted' }}">{{ $count ?: '–' }}</td>@endforeach<td class="number total">{{ $subject['scheduled_periods'] }}</td></tr>
@endforeach
@if($load['scheduled_total'])<tr class="subtotal"><th colspan="4">{{ $teacher['teacher_name'] }} · occupied periods</th>@foreach($load['days'] as $count)<td class="number">{{ $count ?: '–' }}</td>@endforeach<td class="number total">{{ $load['scheduled_total'] }}</td></tr>@endif
@endif
@endforeach
@if(!$rowCount)<tr><td colspan="{{ $schedule['cycle_length'] + ($report === 'teacher-loads' ? 3 : 5) }}">No scheduled teaching periods match this report.</td></tr>@endif
</tbody><tfoot><tr><th colspan="{{ $report === 'teacher-loads' ? 2 : 4 }}">Total teacher periods</th>
@foreach(range(1, max(1, $schedule['cycle_length'])) as $day)<td class="number">{{ collect($summary['teachers'])->sum(fn ($teacher) => data_get(collect($teacher['schedules'])->firstWhere('setting_id', $schedule['id']), 'days.'.$day, 0)) }}</td>@endforeach
<td class="number total">{{ collect($summary['teachers'])->sum(fn ($teacher) => collect($teacher['schedules'])->firstWhere('setting_id', $schedule['id'])['scheduled_total'] ?? 0) }}</td></tr></tfoot></table></div>
</section>
@endforeach
@if(!$summary['schedules'])<p>No scheduled teaching periods match this report.</p>@endif
