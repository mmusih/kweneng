@props(['href' => null, 'label', 'value', 'hint'])
@if($href)
    <a href="{{ $href }}" class="ops-stat"><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $hint }} <span aria-hidden="true">→</span></small></a>
@else
    <div class="ops-stat"><span>{{ $label }}</span><strong>{{ $value }}</strong><small>{{ $hint }}</small></div>
@endif
