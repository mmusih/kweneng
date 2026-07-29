<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableDay extends Model
{
    protected $fillable = [
        'timetable_template_id',
        'day_number',
        'name',
        'weekday',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TimetableTemplate::class, 'timetable_template_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(TimetablePeriod::class)->orderBy('sequence');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
