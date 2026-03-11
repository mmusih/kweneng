<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_FINALIZED = 'finalized';
    const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'locked',
        'status',
        'report_title',
        'report_footer_note',
        'report_office_note',
        'report_extra_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'locked' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
}
