<?php

namespace App\Models\Tt;

use App\Support\Timetable\Bitmask;
use Database\Factories\Tt\TermsDefFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermsDef extends Model
{
    use HasFactory;

    protected $table = 'tt_termsdefs';

    protected $fillable = [
        'tt_setting_id',
        'name',
        'short_name',
        'terms',
        'asc_id',
    ];

    protected static function newFactory(): TermsDefFactory
    {
        return TermsDefFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    /**
     * The alternatives this def offers. The school cuts a separate file per term
     * (§8), so this is a single "Whole year" mask in practice; it is kept for
     * round-trip fidelity with schools that use aSc's term bitmasks properly.
     *
     * @return list<Bitmask>
     */
    public function masks(): array
    {
        return DaysDef::splitMasks($this->terms);
    }
}
