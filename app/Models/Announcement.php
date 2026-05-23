<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'message',
        'type',
        'audience',
        'class_id',
        'subject_id',
        'academic_year_id',
        'term_id',
        'author_id',
        'author_role',
        'is_published',
        'publish_at',
        'expires_at',
    ];

    protected $casts = [
        'publish_at'   => 'datetime',
        'expires_at'   => 'datetime',
        'is_published' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForParents($query)
    {
        return $query->where(function ($q) {
            $q->where('audience', 'all')
                ->orWhere('audience', 'parents')
                ->orWhere('audience', 'specific_class');
        });
    }

    public function scopeForTeachers($query)
    {
        return $query->where(function ($q) {
            $q->where('audience', 'all')
                ->orWhere('audience', 'teachers');
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('publish_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Check if an announcement is relevant to a specific parent.
     * Handles audience targeting including specific_class matching
     * against the parent's children's classes.
     */
    public function isRelevantToParent($parent): bool
    {
        // Broadcast audiences — always relevant
        if (in_array($this->audience, ['all', 'parents'])) {
            return true;
        }

        // Specific class — check if any of the parent's children are in that class
        if ($this->audience === 'specific_class' && $this->class_id) {
            $childClassIds = $parent->students()
                ->pluck('current_class_id')
                ->filter()
                ->toArray();

            return in_array($this->class_id, $childClassIds);
        }

        return false;
    }

    public static function getTypeColor($type)
    {
        return match ($type) {
            'urgent'   => 'red',
            'event'    => 'purple',
            'academic' => 'blue',
            'general'  => 'gray',
            default    => 'gray',
        };
    }

    public static function getTypeIcon($type)
    {
        return match ($type) {
            'urgent'   => '🚨',
            'event'    => '📅',
            'academic' => '📚',
            'general'  => '📢',
            default    => '📢',
        };
    }

    public static function getAudienceLabel($audience)
    {
        return match ($audience) {
            'all'              => 'Everyone',
            'parents'          => 'Parents Only',
            'teachers'         => 'Teachers Only',
            'students'         => 'Students Only',
            'specific_class'   => 'Specific Class',
            'specific_subject' => 'Specific Subject',
            default            => ucfirst($audience),
        };
    }
}
