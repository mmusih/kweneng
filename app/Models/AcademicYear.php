<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    // Define constants for status values
    const STATUS_OPEN = 'open';
    const STATUS_LOCKED = 'locked';
    const STATUS_CLOSED = 'closed';
    
    protected $fillable = [
        'year_name',
        'active',
        'status'
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    public function studentClassHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }
    
    // Helper methods
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
    
    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
    
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
    
    public function isActive(): bool
    {
        return $this->active;
    }
}
