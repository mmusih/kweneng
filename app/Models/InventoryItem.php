<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_EQUIPMENT = 'equipment';
    public const TYPE_STATIONERY = 'stationery';
    public const TYPE_SUPPLY = 'supply';
    public const TYPE_FURNITURE = 'furniture';
    public const TYPE_ICT = 'ict';
    public const TYPE_OTHER = 'other';

    public const CONDITION_SERVICEABLE = 'serviceable';
    public const CONDITION_DAMAGED = 'damaged';
    public const CONDITION_NEEDS_REPAIR = 'needs_repair';
    public const CONDITION_BROKEN = 'broken';
    public const CONDITION_LOST = 'lost';
    public const CONDITION_RETIRED = 'retired';

    public const PROCUREMENT_NONE = 'none';
    public const PROCUREMENT_NEEDS_BUYING = 'needs_buying';
    public const PROCUREMENT_REQUESTED = 'requested';
    public const PROCUREMENT_ORDERED = 'ordered';
    public const PROCUREMENT_RECEIVED = 'received';

    protected $fillable = [
        'name',
        'item_type',
        'category',
        'asset_tag',
        'serial_number',
        'location',
        'unit',
        'quantity_on_hand',
        'minimum_quantity',
        'condition_status',
        'procurement_status',
        'purchase_date',
        'purchase_cost',
        'description',
        'notes',
        'last_checked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'last_checked_at' => 'datetime',
        'quantity_on_hand' => 'integer',
        'minimum_quantity' => 'integer',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_EQUIPMENT => 'Equipment',
            self::TYPE_STATIONERY => 'Stationery',
            self::TYPE_SUPPLY => 'Supplies',
            self::TYPE_FURNITURE => 'Furniture',
            self::TYPE_ICT => 'ICT',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public static function conditions(): array
    {
        return [
            self::CONDITION_SERVICEABLE => 'Serviceable',
            self::CONDITION_DAMAGED => 'Damaged',
            self::CONDITION_NEEDS_REPAIR => 'Needs repair',
            self::CONDITION_BROKEN => 'Broken',
            self::CONDITION_LOST => 'Lost',
            self::CONDITION_RETIRED => 'Retired',
        ];
    }

    public static function procurementStatuses(): array
    {
        return [
            self::PROCUREMENT_NONE => 'None',
            self::PROCUREMENT_NEEDS_BUYING => 'Needs buying',
            self::PROCUREMENT_REQUESTED => 'Requested',
            self::PROCUREMENT_ORDERED => 'Ordered',
            self::PROCUREMENT_RECEIVED => 'Received',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function requisitionItems()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function needsAttention(): bool
    {
        return $this->quantity_on_hand <= $this->minimum_quantity
            || in_array($this->condition_status, [self::CONDITION_DAMAGED, self::CONDITION_NEEDS_REPAIR, self::CONDITION_BROKEN], true)
            || $this->procurement_status === self::PROCUREMENT_NEEDS_BUYING;
    }
}
