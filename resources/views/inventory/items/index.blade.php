<x-app-layout>
    <x-slot name="header"><div class="mt-16 p-5 kw-page-header rounded-lg"><h2 class="text-2xl font-semibold text-white">Inventory Items</h2></div></x-slot>
    <div class="py-8 kw-soft-section min-h-screen"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))<div class="p-4 rounded bg-green-50 text-green-800 border border-green-200">{{ session('success') }}</div>@endif
        <div class="bg-white kw-panel p-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <input name="search" value="{{ request('search') }}" placeholder="Search item, tag, location" class="md:col-span-2 rounded-md border-gray-300">
                <select name="item_type" class="rounded-md border-gray-300"><option value="">All types</option>@foreach($types as $v=>$l)<option value="{{ $v }}" @selected(request('item_type')===$v)>{{ $l }}</option>@endforeach</select>
                <select name="condition_status" class="rounded-md border-gray-300"><option value="">All conditions</option>@foreach($conditions as $v=>$l)<option value="{{ $v }}" @selected(request('condition_status')===$v)>{{ $l }}</option>@endforeach</select>
                <select name="procurement_status" class="rounded-md border-gray-300"><option value="">Buying status</option>@foreach($procurementStatuses as $v=>$l)<option value="{{ $v }}" @selected(request('procurement_status')===$v)>{{ $l }}</option>@endforeach</select>
                <div class="flex gap-2"><button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Filter</button><a href="{{ route('inventory.items.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-md">Add</a></div>
                <label class="md:col-span-6 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="attention" value="1" @checked(request()->boolean('attention'))> Show only low stock, broken or needs buying</label>
            </form>
        </div>
        <div class="bg-white kw-panel overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Item</th><th class="px-4 py-3 text-left">Type</th><th class="px-4 py-3 text-left">Location</th><th class="px-4 py-3 text-left">Qty</th><th class="px-4 py-3 text-left">Condition</th><th class="px-4 py-3 text-left">Buying</th><th></th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($items as $item)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('inventory.items.show', $item) }}" class="font-semibold text-indigo-600">{{ $item->name }}</a><div class="text-xs text-gray-500">{{ $item->asset_tag ?: $item->serial_number }}</div></td>
                        <td class="px-4 py-3">{{ $types[$item->item_type] ?? $item->item_type }}</td>
                        <td class="px-4 py-3">{{ $item->location ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $item->quantity_on_hand }} {{ $item->unit }} @if($item->quantity_on_hand <= $item->minimum_quantity)<span class="ml-1 text-red-600 text-xs">low</span>@endif</td>
                        <td class="px-4 py-3">{{ $conditions[$item->condition_status] ?? $item->condition_status }}</td>
                        <td class="px-4 py-3">{{ $procurementStatuses[$item->procurement_status] ?? $item->procurement_status }}</td>
                        <td class="px-4 py-3 text-right"><a class="text-indigo-600" href="{{ route('inventory.items.edit', $item) }}">Edit</a></td>
                    </tr>
                @empty<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No inventory items found.</td></tr>@endforelse
                </tbody></table>
        </div>
        {{ $items->links() }}
    </div></div>
</x-app-layout>
