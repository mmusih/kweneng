<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFinanceAccount extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['opened_on' => 'date'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function charges()
    {
        return $this->hasMany(StudentFinanceCharge::class);
    }

    public function payments()
    {
        return SchoolPaymentItem::where('student_id', $this->student_id)->where('kind', 'fees')
            ->whereHas('payment', fn ($q) => $q->where('status', 'confirmed')->whereDate('paid_on', '>=', $this->opened_on));
    }

    public function balanceMinor(): int
    {
        return $this->opening_balance_minor + $this->charges()->sum('amount_minor') - $this->payments()->sum('amount_minor');
    }
}
