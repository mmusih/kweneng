<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeaveType extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['working_days' => 'array', 'uses_allowance' => 'boolean', 'requires_document' => 'boolean', 'exclude_holidays' => 'boolean', 'active' => 'boolean'];
}
