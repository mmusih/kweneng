<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDocument extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['path'];

    protected $casts = ['issued_on' => 'date', 'expires_on' => 'date', 'does_not_expire' => 'boolean', 'verified_at' => 'datetime'];

    public function requirement()
    {
        return $this->belongsTo(StaffRequirement::class, 'staff_requirement_id');
    }
}
