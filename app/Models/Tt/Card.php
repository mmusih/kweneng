<?php

namespace App\Models\Tt;

use App\Support\Timetable\Bitmask;
use Database\Factories\Tt\CardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory;

    protected $table = 'tt_cards';

    protected $fillable = [
        'tt_lesson_id',
        'period_number',
        'days',
        'weeks',
        'terms',
        'tt_room_id',
        'locked',
    ];

    protected $casts = [
        'locked' => 'boolean',
    ];

    protected static function newFactory(): CardFactory
    {
        return CardFactory::new();
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'tt_lesson_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'tt_room_id');
    }

    // Masks are methods, not casts: the days width comes from the owning setting's
    // cycle_length (6 here, never a constant), which a cast cannot reach.
    public function daysMask(): Bitmask
    {
        $width = (int) ($this->lesson?->setting?->cycle_length ?? 0);

        if ($width < 1) {
            $width = strlen((string) $this->days);
        }

        return new Bitmask((string) $this->days, $width);
    }

    public function weeksMask(): Bitmask
    {
        return new Bitmask((string) $this->weeks, strlen((string) $this->weeks));
    }

    public function termsMask(): Bitmask
    {
        return new Bitmask((string) $this->terms, strlen((string) $this->terms));
    }
}
