<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['paid_on' => 'date', 'confirmed_at' => 'datetime', 'reversed_at' => 'datetime', 'emailed_at' => 'datetime', 'amount_minor' => 'integer'];

    public function items()
    {
        return $this->hasMany(SchoolPaymentItem::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
