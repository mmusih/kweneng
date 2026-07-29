<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scheme extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CHANGES_REQUESTED = 'changes_requested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'teacher_subject_id',
        'syllabus_id',
        'academic_year_id',
        'created_by',
        'reviewed_by',
        'title',
        'status',
        'submitted_at',
        'reviewed_at',
        'review_comment',
        'notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function teacherSubject(): BelongsTo
    {
        return $this->belongsTo(TeacherSubject::class);
    }

    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SchemeItem::class)->orderBy('term_id')->orderBy('week_number')->orderBy('planned_order')->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SchemeProgressLog::class)->latest();
    }

    public function plannedItems(): HasMany
    {
        return $this->items()->whereNotNull('term_id')->whereNotNull('week_number');
    }

    public function completionPct(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->with('subtopics')->get();

        $total = 0;
        $done = 0;

        foreach ($items as $item) {
            $subtopics = $item->relationLoaded('subtopics') ? $item->subtopics : $item->subtopics()->get();

            if ($subtopics->count() > 0) {
                $total += $subtopics->count();
                $done += $subtopics->where('status', SchemeItemSubtopic::STATUS_COMPLETED)->count();
            } else {
                $total++;
                if ($item->status === SchemeItem::STATUS_COMPLETED) {
                    $done++;
                }
            }
        }

        return $total > 0 ? round(($done / $total) * 100, 1) : 0.0;
    }

    public function expectedPct(?Carbon $date = null): float
    {
        $date ??= now();
        $items = $this->items()->with('term')->whereNotNull('term_id')->whereNotNull('week_number')->get();
        $total = $items->count();

        if ($total === 0) {
            return 0.0;
        }

        $expected = $items->filter(function (SchemeItem $item) use ($date) {
            if (!$item->term || !$item->term->start_date || !$item->week_number) {
                return false;
            }

            $weekEnd = $item->term->start_date->copy()->addDays(($item->week_number * 7) - 1)->endOfDay();

            return $weekEnd->lessThanOrEqualTo($date);
        })->count();

        return round(($expected / $total) * 100, 1);
    }

    public function pacingStatus(): string
    {
        $actual = $this->completionPct();
        $expected = $this->expectedPct();
        $difference = $actual - $expected;

        if ($this->items()->count() === 0) {
            return 'no_plan';
        }

        if ($difference >= 8) {
            return 'ahead';
        }

        if ($difference >= -7) {
            return 'on_track';
        }

        if ($difference >= -20) {
            return 'behind';
        }

        return 'critical';
    }

    public function lastProgressAt(): ?Carbon
    {
        return $this->logs()->latest('created_at')->value('created_at');
    }
}
