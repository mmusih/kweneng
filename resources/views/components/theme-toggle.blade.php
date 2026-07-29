<button
    type="button"
    data-theme-toggle
    aria-pressed="false"
    aria-label="Switch to dark mode"
    title="Switch to dark mode"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center rounded-md p-2 text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-slate-600 dark:focus-visible:ring-offset-brand-900',
    ]) }}
>
    <x-icon name="moon" class="h-5 w-5 dark:hidden" />
    <x-icon name="sun" class="hidden h-5 w-5 dark:block" />
    <span class="sr-only" data-theme-label>Switch to dark mode</span>
</button>
