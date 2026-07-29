<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-slate-800 rounded-2xl shadow-sm border border-slate-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-white">Register & Calendar Dashboard</h2>
                    <p class="text-slate-400 text-sm mt-1">
                        Monitor class teacher registers and maintain school calendar dates
                    </p>
                </div>
                @if (($stats['missing'] ?? 0) > 0)
                    <a href="{{ route('register-officer.registers.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/15 transition-colors">
                        <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                        {{ $stats['missing'] }} register{{ $stats['missing'] === 1 ? '' : 's' }} missing today
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if ($stats['holiday'])
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 shadow-sm">
                    Today is marked as a holiday: <strong>{{ $stats['holiday_title'] }}</strong>. Registers are not expected.
                </div>
            @endif

            @php
                $statCards = [
                    [
                        'label' => 'Class Teacher Classes',
                        'value' => $stats['classes'] ?? 0,
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                    ],
                    [
                        'label' => 'Recorded Today',
                        'value' => $stats['recorded'] ?? 0,
                        'icon_bg' => 'bg-teal-50',
                        'icon_color' => 'text-teal-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    ],
                    [
                        'label' => 'Missing Today',
                        'value' => $stats['missing'] ?? 0,
                        'icon_bg' => 'bg-rose-50',
                        'icon_color' => 'text-rose-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.667 1.73-3L13.73 4c-.77-1.333-2.69-1.333-3.46 0L3.2 16c-.77 1.333.19 3 1.73 3z"/>',
                    ],
                    [
                        'label' => 'Upcoming Events',
                        'value' => $stats['upcoming_events'] ?? 0,
                        'icon_bg' => 'bg-slate-100',
                        'icon_color' => 'text-slate-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                    ],
                ];

                $actionCards = [
                    [
                        'title' => 'Register Monitor',
                        'description' => 'See who has not recorded attendance.',
                        'route' => route('register-officer.registers.index'),
                        'accent' => 'border-[#7F6B6B]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 104 0M9 5a2 2 0 012 2m0-2a2 2 0 012 2"/>',
                        'badge' => ($stats['missing'] ?? 0) > 0 ? ($stats['missing'] . ' missing today') : null,
                    ],
                    [
                        'title' => 'Events List',
                        'description' => 'Manage events, calendar dates and holidays.',
                        'route' => route('register-officer.events.index'),
                        'accent' => 'border-[#4A4E69]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                    ],
                    [
                        'title' => 'Calendar',
                        'description' => 'Mark holidays and school events.',
                        'route' => route('register-officer.events.calendar'),
                        'accent' => 'border-[#2F4F4F]',
                        'icon' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="1.75"/><line x1="16" y1="2" x2="16" y2="6" stroke-width="1.75"/><line x1="8" y1="2" x2="8" y2="6" stroke-width="1.75"/><line x1="3" y1="10" x2="21" y2="10" stroke-width="1.75"/>',
                    ],
                    [
                        'title' => 'Add Holiday/Event',
                        'description' => 'Holiday events automatically affect registers.',
                        'route' => route('register-officer.events.create'),
                        'accent' => 'border-[#4A5D23]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>',
                    ],
                ];
            @endphp

            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">At a glance</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
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
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed">{{ $card['description'] }}</p>
                            @if (!empty($card['badge']))
                                <span class="mt-3 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                    {{ $card['badge'] }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-900">Upcoming Calendar Items</h3>
                    <span class="text-sm text-slate-500">{{ $academicYear?->year_name ?? 'No active academic year' }}</span>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    @forelse ($upcomingEvents as $event)
                        <a href="{{ route('register-officer.events.show', $event) }}" class="rounded-xl border border-slate-100 bg-slate-50 p-4 hover:bg-slate-100">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900 truncate">{{ $event->title }}</p>
                                @if ($event->type === \App\Models\Event::TYPE_HOLIDAY)
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-800">Holiday</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-2">{{ ucfirst($event->type) }} • {{ $event->start_datetime->format('d M Y, H:i') }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No upcoming events.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
