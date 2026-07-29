@props([
    'title',
    'body',
    'meta',
    'route',
    'icon',
    'iconClass' => 'bg-sky-500/10 text-sky-600',
    // Semantic tone for the meta chip: slate | rose | amber | emerald | indigo | sky
    'metaTone'  => 'slate',
])

@php
    $chipTones = [
        'slate'   => 'bg-[#D3D9D4]/40 text-[#2E3944] ring-[#748D92]/20 dark:bg-brand-700 dark:text-brand-200 dark:ring-brand-600',
        'rose'    => 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-950/60 dark:text-rose-200 dark:ring-rose-800',
        'amber'   => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-950/60 dark:text-amber-200 dark:ring-amber-800',
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-200 dark:ring-emerald-800',
        'indigo'  => 'bg-indigo-100 text-indigo-800 ring-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-200 dark:ring-indigo-800',
        'sky'     => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950/60 dark:text-sky-200 dark:ring-sky-800',
    ];
    $chip = $chipTones[$metaTone] ?? $chipTones['slate'];
@endphp

<a href="{{ $route }}"
    {{ $attributes->merge(['class' => 'group rounded-xl border border-[#D3D9D4] bg-white transition-all duration-200 p-5 shadow-sm block hover:shadow-md hover:-translate-y-0.5 hover:border-[#124E66]/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#124E66] focus-visible:ring-offset-2 dark:bg-brand-800 dark:border-brand-600 dark:hover:border-brand-400 dark:focus-visible:ring-offset-brand-900 motion-reduce:transform-none motion-reduce:transition-none']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-3 min-w-0">
            <div class="p-2.5 rounded-lg shrink-0 {{ $iconClass }}">
                <x-icon :name="$icon" class="w-6 h-6" />
            </div>
            <div class="min-w-0">
                <h4 class="font-bold text-[#212A31] dark:text-white">{{ $title }}</h4>
                <p class="text-sm text-[#748D92] mt-1 dark:text-brand-400">{{ $body }}</p>
            </div>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-bold ring-1 shrink-0 whitespace-nowrap {{ $chip }}">{{ $meta }}</span>
    </div>
</a>
