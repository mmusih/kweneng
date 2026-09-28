<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $title }}</title><style>
    @page { size: A4 landscape; margin: 7mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #17202a; }
    .head { width: 100%; margin-bottom: 6px; } .head td { vertical-align: middle; }
    .logo { width: 42px; } h1 { margin: 0; font-size: 15px; } .meta { margin-top: 2px; color: #475569; }
    table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .grid th, .grid td { border: 1px solid #64748b; padding: 3px; vertical-align: top; }
    .grid th { background: #124e66; color: white; text-align: center; }
    .period { width: 72px; background: #e2e8f0; font-weight: bold; }
    .entry + .entry { border-top: 1px dashed #94a3b8; margin-top: 2px; padding-top: 2px; }
    .subject { font-weight: bold; font-size: 9px; } .detail { color: #334155; line-height: 1.2; }
</style></head><body>
<table class="head"><tr>
    <td style="width:50px">@if (is_file($logoPath))<img src="{{ $logoPath }}" class="logo">@endif</td>
    <td><h1>{{ $schoolName }}</h1><div class="meta">{{ $title }} · {{ $setting->name }}{{ $setting->term_label ? ' · '.$setting->term_label : '' }}</div></td>
    <td style="text-align:right">Printed {{ now()->format('d M Y') }}</td>
</tr></table>
<table class="grid"><thead><tr><th class="period">Period</th>@foreach ($days as $day)<th>Day {{ $day }}</th>@endforeach</tr></thead><tbody>
@foreach ($periods as $period)<tr><td class="period">{{ $period->name }}<br>{{ substr($period->start_time, 0, 5) }}–{{ substr($period->end_time, 0, 5) }}</td>
    @foreach ($days as $day)<td>@foreach (data_get($cells, "$day.$period->period_number", []) as $entry)<div class="entry">
        <div class="subject">{{ $entry['subject'] }}</div>
        <div class="detail">
            @if ($type !== 'class'){{ $entry['classes'] }}@if ($entry['groups']) · {{ $entry['groups'] }}@endif<br>@endif
            @if ($type !== 'teacher'){{ $entry['teachers'] }}<br>@endif
            @if ($type !== 'room' && $entry['room']){{ $entry['room'] }}@endif
        </div>
    </div>@endforeach</td>@endforeach
</tr>@endforeach
</tbody></table></body></html>
