<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Requisition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RequisitionController extends Controller
{
    public function index(Request $request)
    {
        $query = Requisition::with(['requester', 'items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('requester', function ($requester) use ($search) {
                        $requester->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items', function ($items) use ($search) {
                        $items->where('item_name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    });
            });
        }

        $requisitions = $query->paginate(20)->withQueryString();

        return view('inventory.requisitions.index', [
            'requisitions' => $requisitions,
            'statuses' => Requisition::statuses(),
            'priorities' => Requisition::priorities(),
        ]);
    }

    public function show(Requisition $requisition)
    {
        $requisition->load(['requester', 'handler', 'academicYear', 'term', 'items.inventoryItem']);
        $inventoryItems = InventoryItem::orderBy('name')->get();

        return view('inventory.requisitions.show', [
            'requisition' => $requisition,
            'statuses' => Requisition::statuses(),
            'inventoryItems' => $inventoryItems,
        ]);
    }

    public function update(Request $request, Requisition $requisition)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Requisition::statuses()))],
            'inventory_notes' => ['nullable', 'string', 'max:5000'],
            'linked_inventory_items' => ['nullable', 'array'],
            'linked_inventory_items.*' => ['nullable', 'integer', 'exists:inventory_items,id'],
        ]);

        $oldStatus = $requisition->status;
        $newStatus = $validated['status'];

        $updates = [
            'status' => $newStatus,
            'inventory_notes' => $validated['inventory_notes'] ?? null,
            'handled_by' => Auth::id(),
        ];

        if ($oldStatus !== $newStatus) {
            $timestampColumn = match ($newStatus) {
                Requisition::STATUS_ACKNOWLEDGED => 'acknowledged_at',
                Requisition::STATUS_APPROVED => 'approved_at',
                Requisition::STATUS_ORDERED => 'ordered_at',
                Requisition::STATUS_FULFILLED => 'fulfilled_at',
                Requisition::STATUS_CANCELLED, Requisition::STATUS_REJECTED => 'cancelled_at',
                default => null,
            };

            if ($timestampColumn) {
                $updates[$timestampColumn] = now();
            }
        }

        $requisition->update($updates);

        foreach ($validated['linked_inventory_items'] ?? [] as $itemId => $inventoryItemId) {
            $requisitionItem = $requisition->items()->whereKey($itemId)->first();
            if ($requisitionItem) {
                $requisitionItem->update([
                    'inventory_item_id' => $inventoryItemId ?: null,
                ]);
            }
        }

        return redirect()->route('inventory.requisitions.show', $requisition)
            ->with('success', 'Requisition updated successfully.');
    }

    public function csv(Request $request)
    {
        $query = Requisition::with(['requester', 'handler', 'items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $fileName = 'requisitions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Requested By', 'Title', 'Priority', 'Status', 'Needed By', 'Items', 'Created', 'Handled By']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->reference,
                        $row->requester?->name,
                        $row->title,
                        $row->priority,
                        $row->status,
                        $row->needed_by?->toDateString(),
                        $row->items->map(fn ($item) => $item->item_name . ' x ' . $item->quantity . ' ' . $item->unit)->join('; '),
                        $row->created_at?->format('Y-m-d H:i'),
                        $row->handler?->name,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
