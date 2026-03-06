<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use HasFactory;

    // Define constants for status values
    const STATUS_ACTIVE = 'active';
    const STATUS_FINALIZED = 'finalized';
    const STATUS_LOCKED = 'locked';
    
    protected $fillable = [
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'locked',
        'status'
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
    
    // Helper methods
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
