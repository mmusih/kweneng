<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeaveAllowance extends Model
{
    protected $guarded = ['id'];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    public function type()
    {
        return $this->belongsTo(StaffLeaveType::class, 'staff_leave_type_id');
    }

    public function usedHalfDays(array $statuses = ['approved']): int
    {
        return (int) StaffLeaveRequest::where('staff_profile_id', $this->staff_profile_id)->where('staff_leave_type_id', $this->staff_leave_type_id)->whereYear('starts_on', $this->year)->where('uses_allowance', true)->whereIn('status', $statuses)->sum('half_days');
    }
}
