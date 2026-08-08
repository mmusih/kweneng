<?php

namespace App\Services\Timetable;

use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Tt\Group;
use Illuminate\Support\Facades\DB;

/**
 * Who is actually in a timetable group.
 *
 * Membership is DERIVED, not stored. A group is aSc's name for "the students in this
 * class who take this subject with this teacher", and that fact already lives in
 * `student_subjects` — duplicating it into a pivot would mean two answers that drift
 * apart the moment a student changes an option. `tt_group_student` is an override list
 * only: a student pinned in who the derivation misses, or forced out of one it catches.
 *
 * The derivation needs the group's subject and teacher, which the group itself does not
 * carry — they come from the lesson attached to it. A group with no lesson yet is
 * therefore unanswerable rather than empty, and the two are reported differently.
 */
class GroupMembershipResolver
{
    /**
     * The student ids in this group, ascending.
     *
     * @return list<int>
     */
    public function studentIds(Group $group): array
    {
        $derived = $this->derivedIds($group);

        [$pinned, $excluded] = $this->overrides($group);

        $ids = array_diff(array_unique([...$derived, ...$pinned]), $excluded);

        sort($ids);

        return array_values($ids);
    }

    public function count(Group $group): int
    {
        return count($this->studentIds($group));
    }

    /**
     * True when the group has no lesson, so its subject and teacher — and therefore its
     * membership — are not knowable yet. Distinct from a group that resolves to zero
     * students, which is a data problem in the elections.
     */
    public function isUnresolvable(Group $group): bool
    {
        return ! $group->entire_class && $this->lessonOf($group) === null;
    }

    /**
     * @return list<int>
     */
    private function derivedIds(Group $group): array
    {
        // A whole-class group's roster is the class itself. There is nothing to derive
        // and nothing that can be missing.
        if ($group->entire_class) {
            return Student::query()
                ->where('class_id', $group->class_id)
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        $lesson = $this->lessonOf($group);

        if ($lesson === null) {
            return [];
        }

        $teacherIds = $lesson->teachers->pluck('id')->all();

        return StudentSubject::query()
            ->where('class_id', $group->class_id)
            ->where('subject_id', $lesson->subject_id)
            // A lesson with no teacher on it still identifies a subject set; narrowing by
            // an empty teacher list would wrongly return nobody.
            ->when($teacherIds !== [], fn ($q) => $q->whereIn('teacher_id', $teacherIds))
            ->distinct()
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * The override pivot carries no direction column, so a row means "in". Excluding a
     * student the derivation catches is not expressible yet; when that need arrives it
     * belongs as a nullable `excluded` flag on `tt_group_student` rather than as a
     * second table. Returned as a pair so callers already handle both halves.
     *
     * @return array{0: list<int>, 1: list<int>}
     */
    private function overrides(Group $group): array
    {
        $pinned = DB::table('tt_group_student')
            ->where('tt_group_id', $group->id)
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return [$pinned, []];
    }

    private function lessonOf(Group $group): mixed
    {
        // relationLoaded keeps this cheap in the importer's loop, which eager-loads
        // lessons.teachers for every group up front.
        $lessons = $group->relationLoaded('lessons')
            ? $group->lessons
            : $group->lessons()->with('teachers')->get();

        $lesson = $lessons->first();

        if ($lesson !== null && ! $lesson->relationLoaded('teachers')) {
            $lesson->load('teachers');
        }

        return $lesson;
    }
}
