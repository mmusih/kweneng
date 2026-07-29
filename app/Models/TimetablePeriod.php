<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetablePeriod extends Model
{
    public const TYPES = ['lesson', 'break', 'lunch', 'assembly', 'other'];

    public const TYPE_LESSON = 'lesson';

    protected $fillable = [
        'timetable_day_id',
        'sequence',
        'name',
        'start_time',
        'end_time',
        'type',
    ];

    public function day(): BelongsTo
    {
        return $this->belongsTo(TimetableDay::class, 'timetable_day_id');
    }

    public function isTeaching(): bool
    {
        return $this->type === self::TYPE_LESSON;
    }
}
