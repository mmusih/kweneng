<?php

namespace App\Models\Tt;

use App\Models\AcademicYear;
use App\Support\AcademicYearLabel;
use Database\Factories\Tt\SettingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    use HasFactory;

    public const TYPE_DAY = 'day';

    public const TYPE_AFTERNOON = 'afternoon';

    public const TYPES = [self::TYPE_DAY, self::TYPE_AFTERNOON];

    protected $table = 'tt_settings';

    protected $fillable = [
        'academic_year_id',
        'name',
        'term_label',
        'revision',
        'schedule_type',
        'cycle_length',
        'cycle_anchor_date',
        'cycle_anchor_day',
        'asc_options',
        'is_active',
        'is_published',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'cycle_anchor_date' => 'date',
    ];

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    public function getTermLabelAttribute(?string $value): ?string
    {
        return AcademicYearLabel::singleYear($value);
    }

    public function getNameAttribute(?string $value): ?string
    {
        return AcademicYearLabel::singleYear($value);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(Period::class, 'tt_setting_id')->orderBy('period_number');
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(BreakPeriod::class, 'tt_setting_id');
    }

    public function daysdefs(): HasMany
    {
        return $this->hasMany(DaysDef::class, 'tt_setting_id');
    }

    public function weeksdefs(): HasMany
    {
        return $this->hasMany(WeeksDef::class, 'tt_setting_id');
    }

    public function termsdefs(): HasMany
    {
        return $this->hasMany(TermsDef::class, 'tt_setting_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'tt_setting_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'tt_setting_id');
    }

    public function constraints(): HasMany
    {
        return $this->hasMany(Constraint::class, 'tt_setting_id');
    }

    public function generationRuns(): HasMany
    {
        return $this->hasMany(GenerationRun::class, 'tt_setting_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('schedule_type', $type);
    }

    public static function current(?int $academicYearId = null, string $type = self::TYPE_DAY): ?self
    {
        $academicYearId ??= AcademicYear::current()?->id;

        if (! $academicYearId) {
            return null;
        }

        return static::query()
            ->where('academic_year_id', $academicYearId)
            ->ofType($type)
            ->active()
            ->where('is_published', true)
            ->latest('id')
            ->first();
    }

    public function typeLabel(): string
    {
        return $this->schedule_type === self::TYPE_AFTERNOON ? 'Afternoon / study' : 'Day';
    }
}
