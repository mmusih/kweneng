<?php

namespace App\Models\Tt;

use App\Models\ClassModel;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Factories\Tt\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use HasFactory;

    protected $table = 'tt_lessons';

    protected $fillable = [
        'tt_setting_id',
        'subject_id',
        'periods_per_week',
        'periods_per_card',
        'cards_per_cycle',
        'tt_daysdef_id',
        'tt_weeksdef_id',
        'tt_termsdef_id',
        'seminar_group',
        'capacity',
        'asc_id',
        'preparation_key',
        'assignment_sources',
        'split_key',
    ];

    protected $casts = [
        'periods_per_week' => 'decimal:1',
        'cards_per_cycle' => 'integer',
        'assignment_sources' => 'array',
    ];

    protected static function newFactory(): LessonFactory
    {
        return LessonFactory::new();
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'tt_setting_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function daysDef(): BelongsTo
    {
        return $this->belongsTo(DaysDef::class, 'tt_daysdef_id');
    }

    public function weeksDef(): BelongsTo
    {
        return $this->belongsTo(WeeksDef::class, 'tt_weeksdef_id');
    }

    public function termsDef(): BelongsTo
    {
        return $this->belongsTo(TermsDef::class, 'tt_termsdef_id');
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'tt_lesson_teacher', 'tt_lesson_id', 'teacher_id')
            ->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'tt_lesson_class', 'tt_lesson_id', 'class_id')
            ->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'tt_lesson_group', 'tt_lesson_id', 'tt_group_id')
            ->withTimestamps();
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'tt_lesson_room', 'tt_lesson_id', 'tt_room_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'tt_lesson_id');
    }

    // Number of card GROUPS the lesson needs, not the number of tt_cards rows:
    // a periods_per_card = 2 double is one group occupying two adjacent tt_cards
    // rows on consecutive periods that share a days mask.
    public function cardsRequired(): int
    {
        if ($this->cards_per_cycle !== null) {
            return max(0, (int) $this->cards_per_cycle);
        }

        return (int) ceil((float) $this->periods_per_week / max(1, (int) $this->periods_per_card));
    }
}
