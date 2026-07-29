<x-app-layout>
    <x-slot name="header"><div class="mt-16 p-5 kw-page-header rounded-lg flex justify-between"><h2 class="text-2xl font-semibold text-white">{{ $item->name }}</h2><a class="text-white" href="{{ route('inventory.items.index') }}">Back</a></div></x-slot>
    <div class="py-8 kw-soft-section min-h-screen"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-5">
        @if(session('success'))<div class="p-4 rounded bg-green-50 text-green-800 border">{{ session('success') }}</div>@endif
        <div class="bg-white kw-panel p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Type:</span> {{ $types[$item->item_type] ?? $item->item_type }}</div>
            <div><span class="text-gray-500">Category:</span> {{ $item->category ?: '-' }}</div>
            <div><span class="text-gray-500">Location:</span> {{ $item->location ?: '-' }}</div>
            <div><span class="text-gray-500">Asset Tag:</span> {{ $item->asset_tag ?: '-' }}</div>
            <div><span class="text-gray-500">Serial:</span> {{ $item->serial_number ?: '-' }}</div>
            <div><span class="text-gray-500">Quantity:</span> {{ $item->quantity_on_hand }} {{ $item->unit }} (minimum {{ $item->minimum_quantity }})</div>
            <div><span class="text-gray-500">Condition:</span> {{ $conditions[$item->condition_status] ?? $item->condition_status }}</div>
            <div><span class="text-gray-500">Buying Status:</span> {{ $procurementStatuses[$item->procurement_status] ?? $item->procurement_status }}</div>
            <div class="md:col-span-2"><span class="text-gray-500">Description:</span><div class="mt-1 whitespace-pre-wrap">{{ $item->description ?: '-' }}</div></div>
            <div class="md:col-span-2"><span class="text-gray-500">Notes:</span><div class="mt-1 whitespace-pre-wrap">{{ $item->notes ?: '-' }}</div></div>
        </div>
        <div class="flex justify-end gap-3"><a href="{{ route('inventory.items.edit', $item) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Edit</a><form method="POST" action="{{ route('inventory.items.destroy', $item) }}" onsubmit="return confirm('Archive this item?');">@csrf @method('DELETE')<button class="px-4 py-2 bg-red-600 text-white rounded-md">Archive</button></form></div>
    </div></div>
</x-app-layout>
