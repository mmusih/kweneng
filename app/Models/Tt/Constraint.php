<?php

namespace App\Models\Tt;

use Database\Factories\Tt\ConstraintFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Constraint extends Model
{
    use HasFactory;

    public const WEIGHT_HARD = 100;

    protected $table = 'tt_constraints';

    protected $fillable = [
        'tt_setting_id',
        'kind',
        'weight',
        'params',
        'is_active',
    ];

    protected $casts = [
        'params' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): ConstraintFactory
    {
        return ConstraintFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    public function isHard(): bool
    {
        return (int) $this->weight >= self::WEIGHT_HARD;
    }
}
