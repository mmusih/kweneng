<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-slate-800 rounded-2xl shadow-sm border border-slate-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-white">Inventory Dashboard</h2>
                    <p class="text-slate-400 text-sm mt-1">
                        Equipment, stationery, supplies and teacher requisitions
                    </p>
                </div>
                @if (($stats['new_requisitions'] ?? 0) > 0)
                    <a href="{{ route('inventory.requisitions.index') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-4 py-2 text-sm font-medium text-white hover:bg-white/15 transition-colors">
                        <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                        {{ $stats['new_requisitions'] }} new request{{ $stats['new_requisitions'] === 1 ? '' : 's' }}
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (session('success'))
                <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 p-4 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $statCards = [
                    [
                        'label' => 'Total Items',
                        'value' => $stats['total_items'] ?? 0,
                        'icon_bg' => 'bg-slate-100',
                        'icon_color' => 'text-slate-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                    ],
                    [
                        'label' => 'Needs Repair',
                        'value' => $stats['needs_repair'] ?? 0,
                        'icon_bg' => 'bg-rose-50',
                        'icon_color' => 'text-rose-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35A1.724 1.724 0 005.383 7.75c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.063z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
                    ],
                    [
                        'label' => 'Low Stock',
                        'value' => $stats['low_stock'] ?? 0,
                        'icon_bg' => 'bg-amber-50',
                        'icon_color' => 'text-amber-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.667 1.73-3L13.73 4c-.77-1.333-2.69-1.333-3.46 0L3.2 16c-.77 1.333.19 3 1.73 3z"/>',
                    ],
                    [
                        'label' => 'Needs Buying',
                        'value' => $stats['needs_buying'] ?? 0,
                        'icon_bg' => 'bg-emerald-50',
                        'icon_color' => 'text-emerald-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
                    ],
                    [
                        'label' => 'New Requests',
                        'value' => $stats['new_requisitions'] ?? 0,
                        'icon_bg' => 'bg-indigo-50',
                        'icon_color' => 'text-indigo-600',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                    ],
                ];

                $actionCards = [
                    [
                        'title' => 'Inventory Items',
                        'description' => 'Track equipment, broken items, supplies and stationery.',
                        'route' => route('inventory.items.index'),
                        'accent' => 'border-[#2F4F4F]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                    ],
                    [
                        'title' => 'Teacher Requisitions',
                        'description' => 'Approve, reject, order and fulfil teacher requests.',
                        'route' => route('inventory.requisitions.index'),
                        'accent' => 'border-[#7F6B6B]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
                        'counter' => $stats['new_requisitions'] ?? 0,
                    ],
                    [
                        'title' => 'Add Item',
                        'description' => 'Register a new asset, supply or stationery item.',
                        'route' => route('inventory.items.create'),
                        'accent' => 'border-[#4A5D23]',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>',
                    ],
                ];
            @endphp

            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">At a glance</p>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
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
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white kw-panel border-slate-100">
                    <div class="p-5 border-b border-slate-100"><h3 class="font-semibold text-slate-900">Items Needing Attention</h3></div>
                    <div class="p-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-slate-500"><th class="py-2">Item</th><th>Issue</th><th>Qty</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($attentionItems as $item)
                                    <tr>
                                        <td class="py-3"><a class="text-indigo-600 hover:text-indigo-800" href="{{ route('inventory.items.show', $item) }}">{{ $item->name }}</a></td>
                                        <td>{{ \App\Models\InventoryItem::conditionStatuses()[$item->condition_status] ?? $item->condition_status }}</td>
                                        <td>{{ $item->quantity_on_hand }} {{ $item->unit }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-slate-500">No urgent inventory items.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white kw-panel border-slate-100">
                    <div class="p-5 border-b border-slate-100"><h3 class="font-semibold text-slate-900">Latest Requisitions</h3></div>
                    <div class="p-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-slate-500"><th class="py-2">Ref</th><th>Teacher</th><th>Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($latestRequisitions as $req)
                                    <tr>
                                        <td class="py-3"><a class="text-indigo-600 hover:text-indigo-800" href="{{ route('inventory.requisitions.show', $req) }}">{{ $req->reference }}</a></td>
                                        <td>{{ $req->requester->name ?? 'Unknown' }}</td>
                                        <td>{{ \App\Models\Requisition::statuses()[$req->status] ?? $req->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-slate-500">No requisitions yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
