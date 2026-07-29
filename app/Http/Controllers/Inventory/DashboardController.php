<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Requisition;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_items' => InventoryItem::count(),
            'needs_repair' => InventoryItem::whereIn('condition_status', [
                InventoryItem::CONDITION_DAMAGED,
                InventoryItem::CONDITION_NEEDS_REPAIR,
                InventoryItem::CONDITION_BROKEN,
            ])->count(),
            'low_stock' => InventoryItem::whereColumn('quantity_on_hand', '<=', 'minimum_quantity')->count(),
            'needs_buying' => InventoryItem::where('procurement_status', InventoryItem::PROCUREMENT_NEEDS_BUYING)->count(),
            'new_requisitions' => Requisition::where('status', Requisition::STATUS_SUBMITTED)->count(),
            'urgent_requisitions' => Requisition::where('priority', Requisition::PRIORITY_URGENT)
                ->whereNotIn('status', [Requisition::STATUS_FULFILLED, Requisition::STATUS_REJECTED, Requisition::STATUS_CANCELLED])
                ->count(),
        ];

        $attentionItems = InventoryItem::query()
            ->where(function ($query) {
                $query->whereColumn('quantity_on_hand', '<=', 'minimum_quantity')
                    ->orWhereIn('condition_status', [
                        InventoryItem::CONDITION_DAMAGED,
                        InventoryItem::CONDITION_NEEDS_REPAIR,
                        InventoryItem::CONDITION_BROKEN,
                    ])
                    ->orWhere('procurement_status', InventoryItem::PROCUREMENT_NEEDS_BUYING);
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        $latestRequisitions = Requisition::with(['requester', 'items'])
            ->latest()
            ->limit(10)
            ->get();

        return view('inventory.dashboard', compact('stats', 'attentionItems', 'latestRequisitions'));
    }
}
