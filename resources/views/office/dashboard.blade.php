<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-slate-800 rounded-2xl shadow-sm border border-slate-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-white">Office Dashboard</h2>
                    <p class="text-slate-400 text-sm mt-1">
                        Front office operations, communication, reports, summaries and student profile completion
                    </p>
                </div>
                @if (($stats['unread_messages'] ?? 0) > 0)
                    <a href="{{ route('office.messages.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/15 transition-colors">
                        <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                        {{ $stats['unread_messages'] }} unread message{{ $stats['unread_messages'] === 1 ? '' : 's' }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @php
                $statCards = [
                    [
                        'label' => 'Students',
                        'value' => $stats['students'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                    ],
                    [
                        'label' => 'Incomplete Profiles',
                        'value' => $stats['incomplete_profiles'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                        'icon_bg' => 'bg-amber-50',
                        'icon_color' => 'text-amber-600',
                    ],
                    [
                        'label' => 'Upcoming Events',
                        'value' => $stats['upcoming_events'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                        'icon_bg' => 'bg-teal-50',
                        'icon_color' => 'text-teal-600',
                    ],
                    [
                        'label' => 'Unread Messages',
                        'value' => $stats['unread_messages'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                        'icon_bg' => 'bg-rose-50',
                        'icon_color' => 'text-rose-500',
                    ],
                    [
                        'label' => 'Inventory Alerts',
                        'value' => $stats['inventory_attention'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                        'icon_bg' => 'bg-amber-50',
                        'icon_color' => 'text-amber-600',
                    ],
                    [
                        'label' => 'New Requisitions',
                        'value' => $stats['new_requisitions'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                    ],
                    [
                        'label' => 'Notices',
                        'value' => $stats['announcements'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>',
                        'icon_bg' => 'bg-slate-100',
                        'icon_color' => 'text-slate-500',
                    ],
                    [
                        'label' => 'Calendar Events',
                        'value' => $stats['events'] ?? 0,
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                    ],
                ];

                $actionCards = [
                    [
                        'title' => 'Student Profiles',
                        'description' => 'Update identity, nationality and emergency details.',
                        'route' => route('office.students.index'),
                        'accent' => 'border-indigo-400',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                        'badge' => ($stats['incomplete_profiles'] ?? 0) > 0 ? ($stats['incomplete_profiles'] . ' need completion') : null,
                        'badge_style' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                    ],
                    [
                        'title' => 'Events Management',
                        'description' => 'Create, edit and manage events, meetings, exams and holidays.',
                        'route' => route('office.events.index'),
                        'accent' => 'border-teal-400',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                    ],
                    [
                        'title' => 'Calendar',
                        'description' => 'View the school calendar and mark holiday dates.',
                        'route' => route('office.events.calendar'),
                        'accent' => 'border-slate-400',
                        'icon' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="1.75"/><line x1="16" y1="2" x2="16" y2="6" stroke-width="1.75"/><line x1="8" y1="2" x2="8" y2="6" stroke-width="1.75"/><line x1="3" y1="10" x2="21" y2="10" stroke-width="1.75"/>',
                    ],
                    [
                        'title' => 'Notices',
                        'description' => 'Send and track parent notices and acknowledgements.',
                        'route' => route('office.announcements.index'),
                        'accent' => 'border-violet-400',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>',
                    ],
                    [
                        'title' => 'Messages',
                        'description' => 'Read and reply to parent messages.',
                        'route' => route('office.messages.index'),
                        'accent' => 'border-rose-400',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
                        'counter' => $stats['unread_messages'] ?? 0,
                    ],
                    [
                        'title' => 'Summaries',
                        'description' => 'View, preview and download class summary sheets.',
                        'route' => route('office.exam-summaries.index'),
                        'accent' => 'border-[#6B705C]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                    ],
                    [
                        'title' => 'Reports',
                        'description' => 'Generate individual and bulk report-card PDFs.',
                        'route' => route('office.reports.index'),
                        'accent' => 'border-[#4A5D23]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                    ],
                    [
                        'title' => 'Inventory',
                        'description' => 'Equipment, supplies, stationery and teacher requisitions.',
                        'route' => route('inventory.dashboard'),
                        'accent' => 'border-[#2F4F4F]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                        'badge' => (($stats['inventory_attention'] ?? 0) + ($stats['new_requisitions'] ?? 0)) > 0
                            ? ((($stats['inventory_attention'] ?? 0) + ($stats['new_requisitions'] ?? 0)) . ' item' . ((($stats['inventory_attention'] ?? 0) + ($stats['new_requisitions'] ?? 0)) === 1 ? '' : 's') . ' need attention')
                            : null,
                        'badge_style' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                    ],
                ];
            @endphp

            {{-- Stat cards --}}
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">At a glance</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3">
                    @foreach ($statCards as $card)
                        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3 shadow-sm">
                            <div class="h-8 w-8 rounded-lg {{ $card['icon_bg'] }} {{ $card['icon_color'] }} flex items-center justify-center">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $card['icon'] !!}
                                </svg>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-slate-800 leading-none">{{ $card['value'] }}</p>
                                <p class="text-xs text-slate-500 mt-1.5 leading-tight">{{ $card['label'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Action cards --}}
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Quick actions</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                    @foreach ($actionCards as $card)
                        <a href="{{ $card['route'] }}"
                            class="group relative bg-white rounded-r-xl border border-slate-200 border-l-4 {{ $card['accent'] }} p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-150">

                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-slate-400 group-hover:text-slate-600 transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $card['icon'] !!}
                                    </svg>
                                    <h3 class="text-sm font-semibold text-slate-800">{{ $card['title'] }}</h3>
                                </div>

                                @if (isset($card['counter']))
                                    @if (($card['counter'] ?? 0) > 0)
                                        <span class="inline-flex min-w-[1.25rem] justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-xs font-semibold text-white leading-none shrink-0">
                                            {{ $card['counter'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-teal-200 shrink-0">
                                            Clear
                                        </span>
                                    @endif
                                @endif
                            </div>

                            <p class="text-xs text-slate-500 leading-relaxed">{{ $card['description'] }}</p>

                            @if (!empty($card['badge']))
                                <span class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium {{ $card['badge_style'] ?? 'bg-slate-100 text-slate-600' }}">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ $card['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>