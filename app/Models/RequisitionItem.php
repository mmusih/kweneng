<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'inventory_item_id',
        'item_name',
        'category',
        'unit',
        'quantity',
        'estimated_unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'estimated_unit_cost' => 'decimal:2',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getEstimatedTotalAttribute(): ?float
    {
        if ($this->estimated_unit_cost === null) {
            return null;
        }

        return (float) $this->quantity * (float) $this->estimated_unit_cost;
    }
}
