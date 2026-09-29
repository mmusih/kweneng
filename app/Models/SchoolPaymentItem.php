<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolPaymentItem extends Model
{
    protected $guarded = ['id'];

    public function payment()
    {
        return $this->belongsTo(SchoolPayment::class, 'school_payment_id');
    }
}
