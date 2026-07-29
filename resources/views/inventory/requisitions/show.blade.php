<x-app-layout>
    <x-slot name="header"><div class="mt-16 p-5 kw-page-header rounded-lg flex justify-between"><h2 class="text-2xl font-semibold text-white">{{ $requisition->reference }}</h2><a class="text-white" href="{{ route('inventory.requisitions.index') }}">Back</a></div></x-slot>
    <div class="py-8 kw-soft-section min-h-screen"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-5">
        @if(session('success'))<div class="p-4 rounded bg-green-50 text-green-800 border">{{ session('success') }}</div>@endif
        <div class="bg-white kw-panel p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Requested by:</span> {{ $requisition->requester->name ?? 'Unknown' }}</div>
                <div><span class="text-gray-500">Created:</span> {{ $requisition->created_at->format('d M Y H:i') }}</div>
                <div><span class="text-gray-500">Title:</span> {{ $requisition->title }}</div>
                <div><span class="text-gray-500">Priority:</span> {{ ucfirst($requisition->priority) }}</div>
                <div><span class="text-gray-500">Status:</span> {{ $statuses[$requisition->status] ?? $requisition->status }}</div>
                <div><span class="text-gray-500">Needed by:</span> {{ $requisition->needed_by?->format('d M Y') ?? '-' }}</div>
                <div class="md:col-span-2"><span class="text-gray-500">Reason:</span><div class="whitespace-pre-wrap mt-1">{{ $requisition->reason ?: '-' }}</div></div>
            </div>
        </div>

        <div class="bg-white kw-panel overflow-x-auto">
            <table class="min-w-full text-sm divide-y"><thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Item</th><th class="px-4 py-3 text-left">Category</th><th class="px-4 py-3 text-left">Quantity</th><th class="px-4 py-3 text-left">Linked Inventory Item</th></tr></thead>
            <tbody class="divide-y">
                @foreach($requisition->items as $item)
                    <tr><td class="px-4 py-3">{{ $item->item_name }}<div class="text-xs text-gray-500">{{ $item->notes }}</div></td><td class="px-4 py-3">{{ $item->category ?: '-' }}</td><td class="px-4 py-3">{{ $item->quantity }} {{ $item->unit }}</td><td class="px-4 py-3">{{ $item->inventoryItem->name ?? 'Not linked' }}</td></tr>
                @endforeach
            </tbody></table>
        </div>

        <div class="bg-white kw-panel p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Inventory Action</h3>
            <form method="POST" action="{{ route('inventory.requisitions.update', $requisition) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700">Status</label><select name="status" class="mt-1 w-full rounded-md border-gray-300">@foreach($statuses as $v=>$l)<option value="{{ $v }}" @selected(old('status', $requisition->status)===$v)>{{ $l }}</option>@endforeach</select></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Link requested items to inventory records</label>
                    <div class="mt-2 space-y-2">
                        @foreach($requisition->items as $item)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 items-center">
                                <div class="text-sm">{{ $item->item_name }} x {{ $item->quantity }} {{ $item->unit }}</div>
                                <select name="linked_inventory_items[{{ $item->id }}]" class="rounded-md border-gray-300">
                                    <option value="">Not linked</option>
                                    @foreach($inventoryItems as $inv)
                                        <option value="{{ $inv->id }}" @selected((string) old('linked_inventory_items.'.$item->id, $item->inventory_item_id) === (string) $inv->id)>{{ $inv->name }} ({{ $inv->quantity_on_hand }} {{ $inv->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700">Inventory Notes</label><textarea name="inventory_notes" rows="4" class="mt-1 w-full rounded-md border-gray-300">{{ old('inventory_notes', $requisition->inventory_notes) }}</textarea></div>
                @if($errors->any())<div class="p-4 bg-red-50 text-red-700 rounded border"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <div class="flex justify-end"><button class="px-4 py-2 bg-emerald-600 text-white rounded-md font-semibold">Save Action</button></div>
            </form>
        </div>
    </div></div>
</x-app-layout>
