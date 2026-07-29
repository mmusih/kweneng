<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableRoom extends Model
{
    protected $fillable = [
        'name',
        'code',
        'capacity',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
