<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('asset_tag', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        foreach (['item_type', 'condition_status', 'procurement_status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->boolean('attention')) {
            $query->where(function ($q) {
                $q->whereColumn('quantity_on_hand', '<=', 'minimum_quantity')
                    ->orWhereIn('condition_status', [
                        InventoryItem::CONDITION_DAMAGED,
                        InventoryItem::CONDITION_NEEDS_REPAIR,
                        InventoryItem::CONDITION_BROKEN,
                    ])
                    ->orWhere('procurement_status', InventoryItem::PROCUREMENT_NEEDS_BUYING);
            });
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('inventory.items.index', [
            'items' => $items,
            'types' => InventoryItem::types(),
            'conditions' => InventoryItem::conditions(),
            'procurementStatuses' => InventoryItem::procurementStatuses(),
        ]);
    }

    public function create()
    {
        return view('inventory.items.create', [
            'item' => new InventoryItem([
                'item_type' => InventoryItem::TYPE_EQUIPMENT,
                'condition_status' => InventoryItem::CONDITION_SERVICEABLE,
                'procurement_status' => InventoryItem::PROCUREMENT_NONE,
                'unit' => 'item',
                'quantity_on_hand' => 1,
                'minimum_quantity' => 0,
            ]),
            'types' => InventoryItem::types(),
            'conditions' => InventoryItem::conditions(),
            'procurementStatuses' => InventoryItem::procurementStatuses(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateItem($request);
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['last_checked_at'] = now();

        InventoryItem::create($validated);

        return redirect()->route('inventory.items.index')->with('success', 'Inventory item saved successfully.');
    }

    public function show(InventoryItem $item)
    {
        $item->load(['creator', 'updater', 'requisitionItems.requisition.requester']);

        return view('inventory.items.show', [
            'item' => $item,
            'types' => InventoryItem::types(),
            'conditions' => InventoryItem::conditions(),
            'procurementStatuses' => InventoryItem::procurementStatuses(),
        ]);
    }

    public function edit(InventoryItem $item)
    {
        return view('inventory.items.edit', [
            'item' => $item,
            'types' => InventoryItem::types(),
            'conditions' => InventoryItem::conditions(),
            'procurementStatuses' => InventoryItem::procurementStatuses(),
        ]);
    }

    public function update(Request $request, InventoryItem $item)
    {
        $validated = $this->validateItem($request, $item);
        $validated['updated_by'] = Auth::id();
        $validated['last_checked_at'] = now();

        $item->update($validated);

        return redirect()->route('inventory.items.show', $item)->with('success', 'Inventory item updated successfully.');
    }

    public function destroy(InventoryItem $item)
    {
        $item->delete();

        return redirect()->route('inventory.items.index')->with('success', 'Inventory item archived successfully.');
    }

    protected function validateItem(Request $request, ?InventoryItem $item = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::in(array_keys(InventoryItem::types()))],
            'category' => ['nullable', 'string', 'max:120'],
            'asset_tag' => ['nullable', 'string', 'max:120', Rule::unique('inventory_items', 'asset_tag')->ignore($item?->id)],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'unit' => ['required', 'string', 'max:30'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'minimum_quantity' => ['required', 'integer', 'min:0'],
            'condition_status' => ['required', Rule::in(array_keys(InventoryItem::conditions()))],
            'procurement_status' => ['required', Rule::in(array_keys(InventoryItem::procurementStatuses()))],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
