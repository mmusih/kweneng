<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolPurchaseOrder extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['invoice_path'];

    protected $casts = ['needed_on' => 'date', 'received_on' => 'date', 'reviewed_at' => 'datetime'];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function supplier()
    {
        return $this->belongsTo(SchoolSupplier::class, 'school_supplier_id');
    }

    public function lines()
    {
        return $this->hasMany(SchoolPurchaseLine::class);
    }

    public function payments()
    {
        return $this->hasMany(SchoolPurchasePayment::class);
    }

    public function reference(): string
    {
        return 'KWE-PO-'.str_pad((string) $this->id, 7, '0', STR_PAD_LEFT);
    }

    public function outstandingMinor(): int
    {
        return $this->total_minor - (int) $this->payments()->sum('amount_minor');
    }
}
