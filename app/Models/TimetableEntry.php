<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    protected $fillable = [
        'timetable_template_id',
        'timetable_day_id',
        'start_period_id',
        'end_period_id',
        'class_id',
        'timetable_group_id',
        'subject_id',
        'teacher_id',
        'timetable_room_id',
        'title',
        'notes',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TimetableTemplate::class, 'timetable_template_id');
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(TimetableDay::class, 'timetable_day_id');
    }

    public function startPeriod(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'start_period_id');
    }

    public function endPeriod(): BelongsTo
    {
        return $this->belongsTo(TimetablePeriod::class, 'end_period_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TimetableGroup::class, 'timetable_group_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(TimetableRoom::class, 'timetable_room_id');
    }
}
