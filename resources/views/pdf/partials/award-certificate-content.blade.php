<div class="page" @if($pageBreakAfter ?? false) style="page-break-after: always;" @endif><div class="inner">
    @if(is_file($logoPath))<img src="{{ $logoPath }}" class="logo" alt="School logo">@endif
    <div class="school">Kweneng International Secondary School</div>
    <div class="subtitle">Honour First</div>
    <h1>Certificate of Achievement</h1>
    <div class="presented">This certificate is proudly presented to</div>
    <div class="student">{{ $award->student_name_snapshot }}</div>
    <div class="award-title">{{ $award->award_title }}</div>
    @if($award->citation)<div class="citation">{{ $award->citation }}</div>@endif
    @if($award->position || $award->main_score !== null)<div class="achievement">@if($award->position)Position {{ $award->position }}@endif @if($award->position && $award->main_score !== null) - @endif @if($award->main_score !== null){{ number_format((float)$award->main_score,2) }}%@endif</div>@endif
    <div class="meta">{{ $award->class_name_snapshot }} | {{ $run->academicYear?->year_name }}{{ $run->term ? ' | '.$run->term->name : ' | Annual Award' }} | Presented {{ $run->award_date?->format('j F Y') }}</div>
    <div class="signatures"><div class="signature"><div class="line">Headmaster</div></div><div class="signature right"><div class="line">Date / School Stamp</div></div></div>
    <div class="reference">Certificate reference: {{ $award->certificate_reference }}</div>
</div></div>
