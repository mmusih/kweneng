<?php

namespace App\Models\Tt;

use Database\Factories\Tt\GenerationRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationRun extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'tt_generation_runs';

    protected $fillable = [
        'tt_setting_id',
        'status',
        'seed',
        'placed_count',
        'total_count',
        'hard_violations',
        'soft_score',
        'log',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'log' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected static function newFactory(): GenerationRunFactory
    {
        return GenerationRunFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
