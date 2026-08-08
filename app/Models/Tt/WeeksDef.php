<?php

namespace App\Models\Tt;

use App\Support\Timetable\Bitmask;
use Database\Factories\Tt\WeeksDefFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeksDef extends Model
{
    use HasFactory;

    protected $table = 'tt_weeksdefs';

    protected $fillable = [
        'tt_setting_id',
        'name',
        'short_name',
        'weeks',
        'asc_id',
    ];

    protected static function newFactory(): WeeksDefFactory
    {
        return WeeksDefFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    /**
     * The alternatives this def offers. Same comma-separated aSc form as DaysDef —
     * this school has a single "All weeks" entry, but the format has to survive the
     * round trip for a school that alternates.
     *
     * @return list<Bitmask>
     */
    public function masks(): array
    {
        return DaysDef::splitMasks($this->weeks);
    }
}
