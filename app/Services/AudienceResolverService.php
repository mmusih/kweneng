<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\ClassModel;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Support\Collection;

class AudienceResolverService
{
    public function parentIdsForAnnouncement(Announcement $announcement): Collection
    {
        $announcement->loadMissing('targets');

        if (in_array($announcement->audience, ['all', 'parents', 'all_parents'], true)) {
            return ParentModel::query()
                ->whereNotNull('user_id')
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();
        }

        // Backward compatibility for older single-class announcements.
        if ($announcement->audience === 'specific_class' && $announcement->class_id) {
            return $this->parentIdsForClass((int) $announcement->class_id);
        }

        $parentIds = collect();

        foreach ($announcement->targets as $target) {
            $parentIds = $parentIds->merge(match ($target->target_type) {
                'form_level' => $this->parentIdsForFormLevel((string) $target->target_value),
                'class_group' => $this->parentIdsForClass((int) $target->target_id),
                'parent' => collect([(int) $target->target_id]),
                default => collect(),
            });
        }

        return $parentIds->filter()->unique()->values();
    }

    public function parentCanViewAnnouncement(ParentModel $parent, Announcement $announcement): bool
    {
        return $this->parentIdsForAnnouncement($announcement)->contains((int) $parent->id);
    }

    public function parentIdsForClass(int $classId): Collection
    {
        if ($classId <= 0) {
            return collect();
        }

        return Student::query()
            ->where('current_class_id', $classId)
            ->join('parent_student', 'students.id', '=', 'parent_student.student_id')
            ->pluck('parent_student.parent_id')
            ->filter()
            ->unique()
            ->values();
    }

    public function parentIdsForFormLevel(string $level): Collection
    {
        $level = trim($level);

        if ($level === '') {
            return collect();
        }

        $classIds = ClassModel::query()
            ->where('level', $level)
            ->pluck('id')
            ->filter()
            ->values();

        if ($classIds->isEmpty()) {
            $classIds = ClassModel::query()
                ->where('name', 'like', $level . '%')
                ->pluck('id')
                ->filter()
                ->values();
        }

        if ($classIds->isEmpty()) {
            return collect();
        }

        return Student::query()
            ->whereIn('current_class_id', $classIds)
            ->join('parent_student', 'students.id', '=', 'parent_student.student_id')
            ->pluck('parent_student.parent_id')
            ->filter()
            ->unique()
            ->values();
    }
}
