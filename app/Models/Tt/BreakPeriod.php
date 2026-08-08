<?php

namespace App\Models\Tt;

use Database\Factories\Tt\BreakPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakPeriod extends Model
{
    use HasFactory;

    protected $table = 'tt_breaks';

    protected $fillable = [
        'tt_setting_id',
        'name',
        'short_name',
        'start_time',
        'end_time',
        'after_period',
        'days',
        'asc_id',
    ];

    protected static function newFactory(): BreakPeriodFactory
    {
        return BreakPeriodFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }
}
