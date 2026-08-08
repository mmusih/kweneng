<?php

namespace App\Models\Tt;

use App\Support\Timetable\Bitmask;
use Database\Factories\Tt\DaysDefFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A daysdef is the set of day patterns a lesson is allowed to land on.
 *
 * `days` holds aSc's own comma-separated form — "100000,010000,001000,…" for "Any day",
 * a single "111111" for "Every day" — because §7 fixed the storage format as aSc-style
 * strings and Phase 9 has to re-emit them unchanged.
 *
 * Always read the column through masks(). Passing the raw string to Bitmask would
 * concatenate the whole list into one oversized mask and then truncate it to the cycle
 * width, which fails silently and looks like a solver bug rather than a parsing one.
 */
class DaysDef extends Model
{
    use HasFactory;

    protected $table = 'tt_daysdefs';

    protected $fillable = [
        'tt_setting_id',
        'name',
        'short_name',
        'days',
        'asc_id',
    ];

    protected static function newFactory(): DaysDefFactory
    {
        return DaysDefFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    /**
     * The alternatives this def offers, one Bitmask per comma-separated mask.
     *
     * Width comes from the owning setting's cycle_length so a 6-day rotation is never
     * silently read as 5; it falls back to the stored string's own length when the
     * setting is not loaded.
     *
     * @return list<Bitmask>
     */
    public function masks(): array
    {
        return self::splitMasks($this->days, (int) ($this->setting?->cycle_length ?? 0));
    }

    /**
     * @return list<Bitmask>
     */
    public static function splitMasks(?string $raw, int $width = 0): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $masks = [];

        foreach (explode(',', $raw) as $mask) {
            $mask = trim($mask);

            if ($mask !== '') {
                $masks[] = new Bitmask($mask, $width > 0 ? $width : strlen($mask));
            }
        }

        return $masks;
    }
}
