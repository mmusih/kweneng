<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Event extends Model
{
    use HasFactory;

    public const TYPE_HOLIDAY = 'holiday';

    protected $fillable = [
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'type',
        'visibility',
        'class_id',
        'academic_year_id',
        'created_by',
        'created_by_role',
        'is_all_day',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_all_day' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(EventComment::class, 'event_id');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_datetime', '>=', now());
    }

    public function scopeForParents(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('visibility', 'all')
                ->orWhere('visibility', 'parents');
        });
    }

    public function scopeForTeachers(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('visibility', 'all')
                ->orWhere('visibility', 'teachers');
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeVisibleTo(Builder $query, string $visibility): Builder
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeHoliday(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_HOLIDAY);
    }

    public function scopeOverlappingDateRange(Builder $query, CarbonInterface $fromDate, CarbonInterface $toDate): Builder
    {
        return $query
            ->whereDate('start_datetime', '<=', $toDate->toDateString())
            ->where(function ($q) use ($fromDate) {
                $q->where(function ($singleDay) use ($fromDate) {
                    $singleDay->whereNull('end_datetime')
                        ->whereDate('start_datetime', '>=', $fromDate->toDateString());
                })->orWhereDate('end_datetime', '>=', $fromDate->toDateString());
            });
    }

    public function scopeAffectsAttendance(Builder $query, ?int $classId = null, ?int $academicYearId = null): Builder
    {
        return $query
            ->holiday()
            ->when($academicYearId, function (Builder $q) use ($academicYearId) {
                $q->where(function ($yearQuery) use ($academicYearId) {
                    $yearQuery->whereNull('academic_year_id')
                        ->orWhere('academic_year_id', $academicYearId);
                });
            })
            ->when($classId, function (Builder $q) use ($classId) {
                $q->where(function ($classQuery) use ($classId) {
                    $classQuery->where('visibility', '!=', 'specific_class')
                        ->orWhere(function ($specificClassQuery) use ($classId) {
                            $specificClassQuery->where('visibility', 'specific_class')
                                ->where('class_id', $classId);
                        });
                });
            }, function (Builder $q) {
                $q->where('visibility', '!=', 'specific_class');
            });
    }

    /**
     * Returns a collection keyed by Y-m-d. Each item contains a title and the events on that date.
     */
    public static function attendanceHolidayDatesBetween(
        CarbonInterface|string $fromDate,
        CarbonInterface|string $toDate,
        ?int $classId = null,
        ?int $academicYearId = null
    ): Collection {
        $from = $fromDate instanceof CarbonInterface
            ? Carbon::parse($fromDate->toDateString())->startOfDay()
            : Carbon::parse($fromDate)->startOfDay();

        $to = $toDate instanceof CarbonInterface
            ? Carbon::parse($toDate->toDateString())->startOfDay()
            : Carbon::parse($toDate)->startOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $events = static::query()
            ->affectsAttendance($classId, $academicYearId)
            ->overlappingDateRange($from, $to)
            ->orderBy('start_datetime')
            ->orderBy('title')
            ->get();

        $dates = collect();

        foreach ($events as $event) {
            $eventStart = $event->start_datetime->copy()->startOfDay();
            $eventEnd = ($event->end_datetime ?: $event->start_datetime)->copy()->startOfDay();

            if ($eventEnd->lt($eventStart)) {
                $eventEnd = $eventStart->copy();
            }

            $rangeStart = $eventStart->gt($from) ? $eventStart : $from;
            $rangeEnd = $eventEnd->lt($to) ? $eventEnd : $to;

            foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $date) {
                $key = $date->toDateString();
                $current = $dates->get($key, [
                    'date' => $key,
                    'title' => '',
                    'titles' => [],
                    'events' => collect(),
                ]);

                $current['titles'][$event->id] = $event->title;
                $current['events']->push($event);
                $current['title'] = implode(', ', array_values($current['titles']));

                $dates->put($key, $current);
            }
        }

        return $dates->sortKeys();
    }

    public static function attendanceHolidayForDate(
        CarbonInterface|string $date,
        ?int $classId = null,
        ?int $academicYearId = null
    ): ?array {
        $day = $date instanceof CarbonInterface
            ? Carbon::parse($date->toDateString())->startOfDay()
            : Carbon::parse($date)->startOfDay();

        return static::attendanceHolidayDatesBetween($day, $day, $classId, $academicYearId)
            ->get($day->toDateString());
    }

    public static function getTypeColor($type)
    {
        return match ($type) {
            'holiday' => 'yellow',
            'exam' => 'red',
            'meeting' => 'blue',
            'activity' => 'green',
            'ceremony' => 'purple',
            'other' => 'gray',
            default => 'gray',
        };
    }

    public static function getTypeIcon($type)
    {
        return match ($type) {
            'holiday' => '🌴',
            'exam' => '📝',
            'meeting' => '👥',
            'activity' => '🎉',
            'ceremony' => '🏆',
            'other' => '📢',
            default => '📅'
        };
    }

    public static function getVisibilityLabel($visibility)
    {
        return match ($visibility) {
            'all' => 'All Users',
            'parents' => 'Parents Only',
            'teachers' => 'Teachers Only',
            'students' => 'Students Only',
            'specific_class' => 'Specific Class',
            default => ucfirst(str_replace('_', ' ', $visibility))
        };
    }

    public static function getTypeLabel($type)
    {
        return match ($type) {
            'holiday' => 'Holiday',
            'exam' => 'Exam',
            'meeting' => 'Meeting',
            'activity' => 'Activity',
            'ceremony' => 'Ceremony',
            'other' => 'Other',
            default => ucfirst($type)
        };
    }

    public function isHappening()
    {
        $now = now();
        if ($this->end_datetime) {
            return $now->between($this->start_datetime, $this->end_datetime);
        }
        return $now->isSameDay($this->start_datetime) || $now >= $this->start_datetime;
    }

    public function isUpcoming()
    {
        return $this->start_datetime > now();
    }

    public function isPast()
    {
        if ($this->end_datetime) {
            return $this->end_datetime < now();
        }
        return $this->start_datetime < now();
    }

    public function getDurationInDays()
    {
        if ($this->end_datetime) {
            return $this->start_datetime->diffInDays($this->end_datetime) + 1;
        }
        return 1;
    }

    public function isRelevantToClass($classId)
    {
        if ($this->visibility === 'all' || $this->visibility === 'parents') {
            return true;
        }

        if ($this->visibility === 'specific_class' && $this->class_id == $classId) {
            return true;
        }

        return false;
    }

    public function isRelevantToRole($role)
    {
        if ($this->visibility === 'all') {
            return true;
        }

        return $this->visibility === $role;
    }
}
