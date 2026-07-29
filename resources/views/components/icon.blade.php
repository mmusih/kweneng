{{-- Decorative by default. Pass label="Alerts" to expose a meaningful icon. --}}
@props([
    'name',
    'label' => null,
])

@php
    $icon  = config("icons.$name", []);
    $solid = $icon['solid'] ?? false;
    $body  = $icon['body'] ?? '';

    $svgAttributes = [
        'class' => 'w-6 h-6',
        'viewBox' => $solid ? '0 0 20 20' : '0 0 24 24',
        'fill' => $solid ? 'currentColor' : 'none',
    ];

    if (! $solid) {
        $svgAttributes['stroke'] = 'currentColor';
        $svgAttributes['stroke-width'] = '2';
    }

    if ($label) {
        $svgAttributes['role'] = 'img';
        $svgAttributes['aria-label'] = $label;
    } else {
        $svgAttributes['aria-hidden'] = 'true';
        $svgAttributes['focusable'] = 'false';
    }
@endphp

<svg {{ $attributes->merge($svgAttributes) }}>
    {!! $body !!}
</svg>
