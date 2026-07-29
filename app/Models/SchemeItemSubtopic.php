<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeItemSubtopic extends Model
{
    use HasFactory;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_NEEDS_RETEACHING = 'needs_reteaching';

    protected $fillable = [
        'scheme_item_id',
        'syllabus_subtopic_id',
        'title',
        'sort_order',
        'status',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(SchemeItem::class, 'scheme_item_id');
    }

    public function syllabusSubtopic(): BelongsTo
    {
        return $this->belongsTo(SyllabusSubtopic::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
