<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableCycleAnchor extends Model
{
    protected $fillable = [
        'timetable_template_id',
        'anchor_date',
        'day_number',
        'note',
    ];

    protected $casts = [
        'anchor_date' => 'date',
        'day_number' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TimetableTemplate::class, 'timetable_template_id');
    }
}
