<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_citizen' => 'boolean', 'is_teacher' => 'boolean', 'started_on' => 'date', 'contract_ends_on' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requirements()
    {
        return $this->hasMany(StaffRequirement::class);
    }
}
