<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffLeaveHoliday extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = \Carbon\Carbon::parse($value)->toDateString();
    }
}
