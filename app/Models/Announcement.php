<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AnnouncementTarget;

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
        'push_sent_at',
        'expires_at',
    ];

    protected $casts = [
        'publish_at'   => 'datetime',
        'push_sent_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_published' => 'boolean',
    ];

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

    public function targets()
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    public function readers()
    {
        return $this->belongsToMany(
            ParentModel::class,
            'announcement_reads',
            'announcement_id',
            'parent_id'
        )
            ->withPivot(['read_at', 'acknowledged_at'])
            ->withTimestamps();
    }

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
        return $query->whereIn('audience', [
            'all',
            'parents',
            'all_parents',
            'form_level',
            'class_group',
            'specific_parent',
            'specific_class',
        ]);
    }

    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('publish_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
    }

    public function scopeUnreadByParent($query, $parentId)
    {
        return $query->whereNotExists(function ($sub) use ($parentId) {
            $sub->selectRaw(1)
                ->from('announcement_reads')
                ->whereColumn('announcement_reads.announcement_id', 'announcements.id')
                ->where('announcement_reads.parent_id', $parentId)
                ->whereNotNull('announcement_reads.read_at');
        });
    }

    public function requiresAcknowledgement(): bool
    {
        return in_array($this->type, ['urgent'], true);
    }

    public function isRelevantToParent($parent): bool
    {
        if (! $parent) {
            return false;
        }

        if (in_array($this->audience, ['all', 'parents', 'all_parents'], true)) {
            return true;
        }

        // Backward compatibility for old one-class announcements.
        if ($this->audience === 'specific_class' && $this->class_id) {
            $childClassIds = $parent->students()
                ->pluck('current_class_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->toArray();

            return in_array((int) $this->class_id, $childClassIds, true);
        }

        $this->loadMissing('targets');

        if ($this->audience === 'specific_parent') {
            return $this->targets
                ->where('target_type', 'parent')
                ->pluck('target_id')
                ->map(fn($id) => (int) $id)
                ->contains((int) $parent->id);
        }

        if ($this->audience === 'class_group') {
            $targetClassIds = $this->targets
                ->where('target_type', 'class_group')
                ->pluck('target_id')
                ->map(fn($id) => (int) $id)
                ->all();

            if (empty($targetClassIds)) {
                return false;
            }

            $childClassIds = $parent->students()
                ->pluck('current_class_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->all();

            return count(array_intersect($targetClassIds, $childClassIds)) > 0;
        }

        if ($this->audience === 'form_level') {
            $targetLevels = $this->targets
                ->where('target_type', 'form_level')
                ->pluck('target_value')
                ->map(fn($v) => trim((string) $v))
                ->filter()
                ->values()
                ->all();

            if (empty($targetLevels)) {
                return false;
            }

            $childLevels = $parent->students()
                ->with('currentClass')
                ->get()
                ->pluck('currentClass.level')
                ->map(fn($v) => trim((string) $v))
                ->filter()
                ->values()
                ->all();

            return count(array_intersect($targetLevels, $childLevels)) > 0;
        }

        return false;
    }

    public function markReadByParent($parent): void
    {
        if (! $parent) {
            return;
        }

        $this->readers()->syncWithoutDetaching([
            $parent->id => [
                'read_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function acknowledgeByParent($parent): void
    {
        if (! $parent) {
            return;
        }

        $this->readers()->syncWithoutDetaching([
            $parent->id => [
                'read_at' => now(),
                'acknowledged_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function isReadByParent($parent): bool
    {
        if (! $parent) {
            return false;
        }

        return $this->readers()
            ->where('parent_id', $parent->id)
            ->whereNotNull('announcement_reads.read_at')
            ->exists();
    }

    public function isAcknowledgedByParent($parent): bool
    {
        if (! $parent) {
            return false;
        }

        return $this->readers()
            ->where('parent_id', $parent->id)
            ->whereNotNull('announcement_reads.acknowledged_at')
            ->exists();
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
            'all', 'all_parents', 'parents' => 'All Parents',
            'form_level'                    => 'Form Level',
            'class_group'                   => 'Class Group',
            'specific_parent'               => 'Specific Parent',
            'teachers'                      => 'Teachers Only',
            'students'                      => 'Students Only',
            'specific_class'                => 'Specific Class',
            'specific_subject'              => 'Specific Subject',
            default                         => ucwords(str_replace('_', ' ', (string) $audience)),
        };
    }
}
