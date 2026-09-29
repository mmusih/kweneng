<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffExpenseClaim extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['document_path'];

    protected $casts = ['spent_on' => 'date', 'paid_on' => 'date', 'reviewed_at' => 'datetime'];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function reference(): string
    {
        return 'KWE-EXP-'.str_pad((string) $this->id, 7, '0', STR_PAD_LEFT);
    }
}
