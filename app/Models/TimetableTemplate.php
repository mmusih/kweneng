<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableTemplate extends Model
{
    public const CYCLE_WEEKLY = 'weekly';

    public const CYCLE_ROTATING = 'rotating';

    protected $fillable = [
        'academic_year_id',
        'name',
        'cycle_type',
        'cycle_length',
        'cycle_start_date',
        'is_active',
        'is_published',
    ];

    protected $casts = [
        'cycle_start_date' => 'date',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(TimetableDay::class)->orderBy('day_number');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function cycleAnchors(): HasMany
    {
        return $this->hasMany(TimetableCycleAnchor::class)
            ->orderBy('anchor_date');
    }

    public function dayForDate(Carbon|string|null $date = null): ?TimetableDay
    {
        $date = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date ?? now()))
            ->startOfDay();
        $days = $this->relationLoaded('days') ? $this->days : $this->days()->get();

        if ($this->cycle_type === self::CYCLE_WEEKLY) {
            return $days->firstWhere('weekday', $date->dayOfWeekIso);
        }

        if ($date->isWeekend()) {
            return null;
        }

        $anchor = $this->anchorForDate($date);

        if (! $anchor) {
            return null;
        }

        $cursor = $anchor['date']->copy()->startOfDay();
        $schoolDays = 0;

        while ($cursor->lt($date)) {
            if (! $cursor->isWeekend()) {
                $schoolDays++;
            }
            $cursor->addDay();
        }

        $dayNumber = (
            (($anchor['day_number'] - 1 + $schoolDays) % max(1, (int) $this->cycle_length))
            + 1
        );

        return $days->firstWhere('day_number', $dayNumber);
    }

    /**
     * @return array{date: Carbon, day_number: int}|null
     */
    private function anchorForDate(Carbon $date): ?array
    {
        $anchor = $this->relationLoaded('cycleAnchors')
            ? $this->cycleAnchors
                ->filter(fn (TimetableCycleAnchor $item) => $item->anchor_date->lte($date))
                ->sortByDesc('anchor_date')
                ->first()
            : $this->cycleAnchors()
                ->whereDate('anchor_date', '<=', $date->toDateString())
                ->latest('anchor_date')
                ->first();

        if ($anchor) {
            return [
                'date' => $anchor->anchor_date,
                'day_number' => (int) $anchor->day_number,
            ];
        }

        if (! $this->cycle_start_date || $date->lt($this->cycle_start_date)) {
            return null;
        }

        return [
            'date' => $this->cycle_start_date,
            'day_number' => 1,
        ];
    }
}
