<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

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

    // Relationships
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

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>=', now());
    }

    public function scopeForParents($query)
    {
        return $query->where(function ($q) {
            $q->where('visibility', 'all')
                ->orWhere('visibility', 'parents');
        });
    }

    public function scopeForTeachers($query)
    {
        return $query->where(function ($q) {
            $q->where('visibility', 'all')
                ->orWhere('visibility', 'teachers');
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVisibleTo($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    // Helper Methods
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

    // Check if event is currently happening
    public function isHappening()
    {
        $now = now();
        if ($this->end_datetime) {
            return $now->between($this->start_datetime, $this->end_datetime);
        }
        return $now->isSameDay($this->start_datetime) || $now >= $this->start_datetime;
    }

    // Check if event is in the future
    public function isUpcoming()
    {
        return $this->start_datetime > now();
    }

    // Check if event has passed
    public function isPast()
    {
        if ($this->end_datetime) {
            return $this->end_datetime < now();
        }
        return $this->start_datetime < now();
    }

    // Get event duration in days
    public function getDurationInDays()
    {
        if ($this->end_datetime) {
            return $this->start_datetime->diffInDays($this->end_datetime) + 1;
        }
        return 1;
    }

    // Check if event is relevant to a specific class
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

    // Check if event is relevant to a specific user role
    public function isRelevantToRole($role)
    {
        if ($this->visibility === 'all') {
            return true;
        }

        return $this->visibility === $role;
    }
}
