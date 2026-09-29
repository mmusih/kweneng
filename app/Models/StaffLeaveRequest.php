<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeaveRequest extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['document_path'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'reviewed_at' => 'datetime', 'uses_allowance' => 'boolean'];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    public function type()
    {
        return $this->belongsTo(StaffLeaveType::class, 'staff_leave_type_id');
    }
}
