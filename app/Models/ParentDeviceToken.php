<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentDeviceToken extends Model
{
    protected $fillable = [
        'parent_id',
        'user_id',
        'token',
        'platform',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];
}
