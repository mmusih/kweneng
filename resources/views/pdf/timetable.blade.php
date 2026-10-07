<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
@page { size: A4 landscape; margin: 8mm; }
body { font-family: DejaVu Sans,sans-serif; font-size: 9px; color: #17202a; }
h1 { font-size: 22px; margin: 0 0 5px; } .meta { color: #475569; margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th, td { border: 1px solid #64748b; padding: 3px; vertical-align: top; overflow-wrap: break-word; }
th { background: #e2e8f0; text-align: center; } .day { width: 55px; font-size: 14px; }
.time { font-size: 8px; font-weight: normal; } .break { background: #f1f5f9; width: 48px; color: #64748b; }
.entry { padding: 1px 0; } .entry + .entry { border-top: 1px dashed #94a3b8; }
.subject { font-size: 10px; font-weight: bold; } .detail { font-size: 8px; line-height: 1.15; } tr { page-break-inside: avoid; }
</style></head><body>
@forelse($pages as $page)
<div @if(!$loop->first) style="page-break-before: always" @endif>
<h1>{{ $page['title'] }}</h1>
<div class="meta">{{ $page['schoolName'] }}<br>{{ $page['setting']->name }} · {{ $page['setting']->term_label }} · {{ $page['setting']->is_published ? 'Published' : 'Working timetable' }} · Printed {{ now()->format('d M Y') }}<br>{{ $page['excludedForms'] ? 'Excluded forms: '.implode(', ', $page['excludedForms']) : 'All forms included' }}</div>
<table><thead><tr><th class="day">Day</th>
@foreach($page['periods'] as $period)<th>{{ $period->name }}<br><span class="time">{{ substr($period->start_time,0,5) }} - {{ substr($period->end_time,0,5) }}</span></th>
@foreach($page['breaks']->where('after_period', $period->period_number) as $break)<th class="break">{{ $break->name }}<br><span class="time">{{ substr($break->start_time,0,5) }} - {{ substr($break->end_time,0,5) }}</span></th>@endforeach
@endforeach</tr></thead><tbody>
@foreach($page['days'] as $day)<tr><th class="day">Day {{ $day }}</th>
@foreach(\App\Support\Timetable\PrintCells::row($page['periods'], $page['cells'][$day] ?? [], $page['breaks']) as $cell)<td colspan="{{ $cell['span'] }}" style="height: 57px">
@foreach($cell['entries'] as $entry)<div class="entry">
<div class="subject">{{ $entry['subject'] }}</div><div class="detail">
@if($page['type'] !== 'class'){{ $entry['classes'] }}<br>@endif
@if($page['type'] !== 'class' && $entry['groups']){{ $entry['groups'] }}<br>@endif
@if($page['type'] !== 'teacher'){{ $entry['teacher_codes'] }} · @endif
@if($page['type'] !== 'room'){{ $entry['room'] }}@endif
</div></div>@endforeach</td>
@foreach($page['breaks']->where('after_period', $cell['end']) as $break)<td class="break"></td>@endforeach
@endforeach</tr>@endforeach
</tbody></table>
@if($page['type'] !== 'teacher')<p class="detail"><strong>Teachers:</strong> @foreach($page['teacherLegend'] ?? [] as $code => $name){{ $code }} = {{ $name }}; @endforeach</p>@endif
</div>
@empty<h1>No timetables to print</h1><p>No {{ $type ?? 'selected' }} records match this timetable and form selection.</p>@endforelse
</body></html>
