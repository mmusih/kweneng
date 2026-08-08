<?php

namespace App\Models\Tt;

use Database\Factories\Tt\PeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Period extends Model
{
    use HasFactory;

    protected $table = 'tt_periods';

    protected $fillable = [
        'tt_setting_id',
        'period_number',
        'name',
        'short_name',
        'start_time',
        'end_time',
    ];

    protected static function newFactory(): PeriodFactory
    {
        return PeriodFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }
}
