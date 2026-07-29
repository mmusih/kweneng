@props([
    'icon',
    'label',
    'value',
    'sub'        => null,
    // Colour of the leading icon chip.
    'iconWrap'   => 'bg-indigo-50 text-[#124E66] dark:bg-brand-600/20 dark:text-brand-200',
    'valueClass' => 'text-[#212A31] dark:text-white',
    'subClass'   => 'text-[#748D92] dark:text-brand-400',
])

<div {{ $attributes->merge(['class' => 'bg-white border border-[#D3D9D4] rounded-xl shadow-sm p-5 flex items-center gap-4 transition-colors dark:bg-brand-800 dark:border-brand-600']) }}>
    <div class="p-3 rounded-xl shrink-0 {{ $iconWrap }}">
        <x-icon :name="$icon" class="w-6 h-6" />
    </div>
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-widest text-[#748D92] dark:text-brand-400">{{ $label }}</p>
        <h3 class="mt-1 text-2xl font-bold truncate {{ $valueClass }}">{{ $value }}</h3>
        @if ($sub)
            <p class="text-xs font-semibold mt-0.5 {{ $subClass }}">{{ $sub }}</p>
        @endif
    </div>
</div>
