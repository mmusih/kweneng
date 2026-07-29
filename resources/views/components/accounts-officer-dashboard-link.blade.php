@props(['light' => false])

<a href="{{ route('accounts-officer.dashboard') }}"
    {{ $attributes->class([
        'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        'text-white hover:bg-white/10 focus-visible:ring-white focus-visible:ring-offset-red-600' => $light,
        'border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:ring-emerald-500 dark:border-brand-600 dark:bg-brand-800 dark:text-brand-100 dark:hover:bg-brand-700 dark:focus-visible:ring-offset-brand-900' => ! $light,
    ]) }}>
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
    </svg>
    Back to Dashboard
</a>
