<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchemeItem extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MOVED = 'moved';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_NEEDS_RETEACHING = 'needs_reteaching';

    protected $fillable = [
        'scheme_id',
        'syllabus_topic_id',
        'term_id',
        'week_number',
        'title',
        'description',
        'estimated_periods',
        'planned_order',
        'status',
        'completed_at',
        'completed_by',
        'teacher_comment',
        'hod_comment',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NOT_STARTED => 'Not started',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_MOVED => 'Moved',
            self::STATUS_SKIPPED => 'Skipped',
            self::STATUS_NEEDS_RETEACHING => 'Needs reteaching',
        ];
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function syllabusTopic(): BelongsTo
    {
        return $this->belongsTo(SyllabusTopic::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function subtopics(): HasMany
    {
        return $this->hasMany(SchemeItemSubtopic::class)->orderBy('sort_order')->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SchemeProgressLog::class);
    }

    public function isBehindSchedule(): bool
    {
        if ($this->status === self::STATUS_COMPLETED || !$this->term || !$this->term->start_date || !$this->week_number) {
            return false;
        }

        return $this->term->start_date->copy()->addDays(($this->week_number * 7) - 1)->endOfDay()->isPast();
    }
}
