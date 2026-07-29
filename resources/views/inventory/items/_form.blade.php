@csrf
@if ($item->exists)
    @method('PUT')
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-gray-700">Item Name</label>
        <input name="name" value="{{ old('name', $item->name) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Item Type</label>
        <select name="item_type" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('item_type', $item->item_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Category</label>
        <input name="category" value="{{ old('category', $item->category) }}" placeholder="e.g. Science lab, stationery, sports" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Location</label>
        <input name="location" value="{{ old('location', $item->location) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Asset Tag</label>
        <input name="asset_tag" value="{{ old('asset_tag', $item->asset_tag) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
        @error('asset_tag')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Serial Number</label>
        <input name="serial_number" value="{{ old('serial_number', $item->serial_number) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Quantity on Hand</label>
        <input type="number" min="0" name="quantity_on_hand" value="{{ old('quantity_on_hand', $item->quantity_on_hand ?? 1) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Minimum Quantity</label>
        <input type="number" min="0" name="minimum_quantity" value="{{ old('minimum_quantity', $item->minimum_quantity ?? 0) }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Unit</label>
        <input name="unit" value="{{ old('unit', $item->unit ?? 'item') }}" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Condition</label>
        <select name="condition_status" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            @foreach ($conditions as $value => $label)
                <option value="{{ $value }}" @selected(old('condition_status', $item->condition_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Buying Status</label>
        <select name="procurement_status" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
            @foreach ($procurementStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('procurement_status', $item->procurement_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Purchase Date</label>
        <input type="date" name="purchase_date" value="{{ old('purchase_date', $item->purchase_date?->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Purchase Cost</label>
        <input type="number" step="0.01" min="0" name="purchase_cost" value="{{ old('purchase_cost', $item->purchase_cost) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
    </div>
</div>

<div class="mt-5">
    <label class="block text-sm font-medium text-gray-700">Description</label>
    <textarea name="description" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $item->description) }}</textarea>
</div>
<div class="mt-5">
    <label class="block text-sm font-medium text-gray-700">Notes / Repair Details</label>
    <textarea name="notes" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $item->notes) }}</textarea>
</div>

@if ($errors->any())
    <div class="mt-5 rounded-lg bg-red-50 border border-red-200 text-red-800 p-4">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('inventory.items.index') }}" class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 font-semibold">Cancel</a>
    <button class="px-4 py-2 rounded-md bg-emerald-600 text-white font-semibold">Save Item</button>
</div>
