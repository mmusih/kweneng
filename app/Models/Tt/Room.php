<?php

namespace App\Models\Tt;

use Database\Factories\Tt\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $table = 'tt_rooms';

    protected $fillable = [
        'tt_setting_id',
        'name',
        'short_name',
        'capacity',
        'asc_id',
    ];

    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'tt_room_id');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'tt_lesson_room', 'tt_room_id', 'tt_lesson_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }
}
