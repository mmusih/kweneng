<div class="page" @if($pageBreakAfter ?? false) style="page-break-after: always;" @endif><div class="inner">
    @if(is_file($logoPath))<img src="{{ $logoPath }}" class="logo" alt="School logo">@endif
    <div class="school">Kweneng International Secondary School</div>
    <div class="subtitle">Honour First</div>
    <h1>Certificate of Appointment</h1>
    <div class="presented">This certificate is proudly presented to</div>
    <div class="student">{{ $prefect->student?->user?->name }}</div>
    <div class="presented">who is appointed to serve as</div>
    <div class="role">{{ $prefect->title }}</div>
    <div class="citation">{{ $prefect->certificateCitation() }}</div>
    <div class="duties-label">Assigned duties</div>
    <div class="duties">{{ $prefect->duties }}</div>
    <div class="meta">{{ $prefect->class_name_snapshot ?? $prefect->student?->currentClass?->name }} | {{ $prefect->academicYear?->year_name }} | Appointed {{ $prefect->appointed_on?->format('j F Y') }}{{ $prefect->service_ends_on ? ' | Service through '.$prefect->service_ends_on->format('j F Y') : '' }}</div>
    <div class="signatures"><div class="signature"><div class="line">Headmaster</div></div><div class="signature right"><div class="line">Date / School Stamp</div></div></div>
    <div class="reference">Certificate reference: {{ $prefect->certificate_reference }}</div>
</div></div>
