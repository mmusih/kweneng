<?php

namespace App\Services\Timetable;

use App\Models\ClassModel;
use App\Models\TeacherSubject;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Stable class/subject/teacher keys survive the assignment screen's delete-and-insert saves. */
class AssignmentPreparationService
{
    public function assignments(Setting $setting)
    {
        return TeacherSubject::with(['subject', 'teacher.user', 'class'])
            ->where('academic_year_id', $setting->academic_year_id)
            ->whereHas('class', fn ($q) => $q->where('academic_year_id', $setting->academic_year_id))
            ->whereHas('subject', fn ($q) => $q->where('is_active', true))
            ->whereHas('teacher.user', fn ($q) => $q->where('status', 'active'))
            ->get()->unique(fn ($a) => $this->key($a))->keyBy(fn ($a) => $this->key($a));
    }

    private function key(TeacherSubject $assignment): string
    {
        return implode(':', [$assignment->class_id, $assignment->subject_id, $assignment->teacher_id]);
    }

    private function manualSummary($lessons, TeacherSubject $assignment): array
    {
        $matching = $lessons->whereNull('preparation_key')->filter(fn ($lesson) =>
            $lesson->subject_id == $assignment->subject_id && $lesson->classes->contains('id', $assignment->class_id)
            && $lesson->teachers->contains('id', $assignment->teacher_id));
        $summary = ['singles' => 0, 'doubles' => 0, 'cards' => 0, 'periods' => 0, 'placed' => 0];
        foreach ($matching as $lesson) {
            $placed = count(app(CardPlacementService::class)->unitsFor($lesson));
            $count = max($lesson->cardsRequired(), $placed);
            $span = (int) $lesson->periods_per_card;
            $summary['cards'] += $count;
            $summary['periods'] += $count * $span;
            $summary['placed'] += $placed;
            if ($span === 1) $summary['singles'] += $count;
            if ($span === 2) $summary['doubles'] += $count;
        }
        $summary['signature'] = hash('sha256', $matching->sortBy('id')->map(fn ($lesson) => [
            $lesson->id, $lesson->cardsRequired(), $lesson->periods_per_card, (string) $lesson->updated_at,
        ])->values()->toJson());

        return $summary;
    }

    public function payload(Setting $setting): array
    {
        $lessons = Lesson::with(['classes', 'teachers', 'groups', 'cards', 'rooms'])->where('tt_setting_id', $setting->id)->get();
        $manual = $lessons->whereNull('preparation_key');

        return [
            'version' => (int) $setting->preparation_version,
            'rooms' => Room::where('tt_setting_id', $setting->id)->get(['id', 'name']),
            'classes' => ClassModel::with('timetableDivisions.groups')->where('academic_year_id', $setting->academic_year_id)
                ->orderBy('level')->orderBy('name')->get()->map(fn ($class) => [
                    'id' => $class->id, 'name' => $class->name,
                    'groups' => $class->timetableDivisions->flatMap(fn ($division) => $division->groups->map(fn ($g) => [
                        'id' => $g->id, 'name' => $division->name.' / '.$g->name,
                    ]))->values(),
                ]),
            'assignments' => $this->assignments($setting)->map(fn ($a, $key) => [
                'key' => $key, 'class_id' => (int) $a->class_id, 'class_name' => $a->class->name,
                'subject_id' => (int) $a->subject_id, 'subject' => $a->subject->name,
                'is_core' => (bool) $a->subject->is_core, 'teacher_id' => (int) $a->teacher_id,
                'teacher' => $a->teacher->user->name,
                'manual' => $this->manualSummary($manual, $a),
                'existing' => $manual->contains(fn ($l) => $l->subject_id == $a->subject_id
                    && $l->classes->contains('id', $a->class_id) && $l->teachers->contains('id', $a->teacher_id)),
            ])->values(),
            'units' => $lessons->whereNotNull('preparation_key')->filter(fn ($l) => $l->cardsRequired() > 0)->map(fn ($l) => [
                'key' => $l->preparation_key, 'span' => (int) $l->periods_per_card,
                'sources' => $l->assignment_sources, 'split_key' => $l->split_key,
                'placed' => $l->cards->isNotEmpty(),
                'saved' => true,
                'room_id' => $l->rooms->first()?->id,
            ])->values(),
        ];
    }

    public function save(Setting $setting, array $data): void
    {
        DB::transaction(function () use ($setting, $data) {
            $setting = Setting::whereKey($setting->id)->lockForUpdate()->firstOrFail();
            $this->require((int) $setting->preparation_version === (int) $data['version'], 'This preparation changed in another window. Reload before saving.');
            $assignments = $this->assignments($setting);
            $existing = Lesson::with(['cards', 'classes', 'teachers', 'groups'])->where('tt_setting_id', $setting->id)->get();
            $generated = $existing->whereNotNull('preparation_key')->keyBy('preparation_key');
            $units = $data['units'];
            $manual = $assignments->map(fn ($assignment) => $this->manualSummary($existing, $assignment));
            $totals = $manual->map(fn ($summary) => $summary['periods'])->all();

            foreach ($units as &$unit) {
                $unit['span'] = (int) $unit['span'];
                $unit['split_key'] ??= null;
                $unit['room_id'] = empty($unit['room_id']) ? null : (int) $unit['room_id'];
                $this->require(! $unit['room_id'] || Room::whereKey($unit['room_id'])->where('tt_setting_id', $setting->id)->exists(), 'Choose a room in this timetable.');
                $seenClasses = [];
                $subject = $teacher = null;
                foreach ($unit['sources'] as &$source) {
                    $a = $assignments->get($source['key']);
                    $this->require($a !== null, 'A teaching assignment was removed or is inactive. Remove its draft occurrences or restore the assignment.');
                    $this->require(! in_array($a->class_id, $seenClasses), 'A joint occurrence must use different classes.');
                    $this->require($subject === null || ($subject === $a->subject_id && $teacher === $a->teacher_id), 'Joint occurrences must have the same subject and teacher. Update the teaching assignments first.');
                    $subject = $a->subject_id;
                    $teacher = $a->teacher_id;
                    $seenClasses[] = $a->class_id;
                    $source['group_id'] = empty($source['group_id']) ? null : (int) $source['group_id'];
                    if ($source['group_id']) {
                        $this->require(Group::whereKey($source['group_id'])->where('class_id', $a->class_id)->where('entire_class', false)->whereNotNull('tt_division_id')->exists(), 'Choose an attendance group belonging to the assignment’s class.');
                    }
                    $totals[$source['key']] = ($totals[$source['key']] ?? 0) + $unit['span'];
                    $this->require($totals[$source['key']] <= 40, 'An assignment can use at most 40 periods per cycle.');
                    if ($manual[$source['key']]['cards'] > 0) {
                        $this->require(($data['manual_baselines'][$source['key']] ?? null) === $manual[$source['key']]['signature'],
                            'Existing lesson counts have changed. Reload the assignments before adding cards so existing lessons are not duplicated.');
                    }
                }
                unset($source);
                // Strip browser-only state; only the validated structure is persisted.
                $unit = array_intersect_key($unit, array_flip(['key', 'span', 'sources', 'split_key', 'room_id']));
            }
            unset($unit);

            $this->assignSplitGroups($units, $assignments);
            $bySplit = collect($units)->filter(fn ($u) => $u['split_key'])->groupBy('split_key');
            foreach ($bySplit as $members) {
                $this->require($members->count() >= 2, 'A split row needs at least two occurrences. Move a lone occurrence back to the lesson area.');
                $this->require($members->pluck('span')->unique()->count() === 1, 'All occurrences in a split row must have the same duration.');
                $teachers = [];
                $groups = [];
                foreach ($members as $unit) {
                    $a = $assignments[$unit['sources'][0]['key']];
                    $this->require(! in_array($a->teacher_id, $teachers), 'A teacher cannot teach two different lessons in the same split row.');
                    $teachers[] = $a->teacher_id;
                    foreach ($unit['sources'] as $source) {
                        $classId = $assignments[$source['key']]->class_id;
                        $group = Group::findOrFail($source['group_id']);
                        foreach ($groups[$classId] ?? [] as $other) {
                            $this->require($group->id !== $other->id && $group->tt_division_id === $other->tt_division_id, 'Split lessons must use different groups of the same class division.');
                        }
                        $groups[$classId][] = $group;
                    }
                }
            }

            $kept = [];
            foreach ($units as $unit) {
                $kept[] = $unit['key'];
                $lesson = $generated->get($unit['key']);
                $a = $assignments[$unit['sources'][0]['key']];
                if ($lesson && $lesson->cards->isNotEmpty()) {
                    $this->require($lesson->assignment_sources == $unit['sources'] && (int) $lesson->periods_per_card === $unit['span'] && $lesson->split_key === $unit['split_key'] && $lesson->rooms()->first()?->id == $unit['room_id'], 'Return affected cards to the tray before changing their duration, split, joint classes, room or attendance. Placed cards have been preserved.');

                    continue;
                }
                $lesson ??= new Lesson(['tt_setting_id' => $setting->id, 'preparation_key' => $unit['key']]);
                $lesson->fill(['subject_id' => $a->subject_id, 'periods_per_card' => $unit['span'], 'cards_per_cycle' => 1,
                    'periods_per_week' => $unit['span'], 'assignment_sources' => $unit['sources'], 'split_key' => $unit['split_key']])->save();
                $lesson->teachers()->sync([$a->teacher_id]);
                $lesson->rooms()->sync($unit['room_id'] ? [$unit['room_id'] => ['sort_order' => 0]] : []);
                $lesson->classes()->sync(array_map(fn ($s) => $assignments[$s['key']]->class_id, $unit['sources']));
                $lesson->groups()->sync(array_values(array_filter(array_column($unit['sources'], 'group_id'))));
            }
            foreach ($generated->filter(fn ($lesson) => ! in_array($lesson->preparation_key, $kept, true)) as $lesson) {
                $this->require($lesson->cards->isEmpty(), 'Return affected cards to the tray before removing their occurrences. Placed cards have been preserved.');
                $lesson->delete();
            }
            $setting->increment('preparation_version');
        });
    }

    /** Connected split rows share one division, including their cross-class joint members. */
    private function assignSplitGroups(array &$units, $assignments): void
    {
        $families = [];
        foreach (collect($units)->filter(fn ($u) => $u['split_key'])->groupBy('split_key') as $members) {
            $keys = $members->flatMap(fn ($u) => array_column($u['sources'], 'key'))->unique()->all();
            do {
                $merged = false;
                foreach ($families as $i => $family) {
                    if (array_intersect($keys, $family)) {
                        $keys = array_values(array_unique(array_merge($keys, $family)));
                        unset($families[$i]);
                        $merged = true;
                    }
                }
            } while ($merged);
            $families[] = $keys;
        }
        foreach ($families as $keys) {
            sort($keys);
            $shared = 'prepared-'.substr(hash('sha256', implode('|', $keys)), 0, 24);
            foreach (collect($keys)->groupBy(fn ($key) => $assignments[$key]->class_id) as $classId => $classKeys) {
                $explicit = collect($units)->flatMap(fn ($u) => $u['sources'])->filter(fn ($s) => $classKeys->contains($s['key']) && $s['group_id']);
                $divisionIds = Group::whereIn('id', $explicit->pluck('group_id'))->pluck('tt_division_id')->unique();
                $this->require($divisionIds->count() <= 1, 'The selected split groups belong to different divisions. Choose groups from one division.');
                $division = $divisionIds->isNotEmpty() ? Division::findOrFail($divisionIds->first()) : Division::firstOrCreate(
                    ['class_id' => $classId, 'shared_key' => $shared],
                    ['name' => 'Prepared split', 'division_tag' => ((int) Division::where('class_id', $classId)->max('division_tag')) + 1],
                );
                foreach ($classKeys as $key) {
                    $groupId = $explicit->firstWhere('key', $key)['group_id'] ?? null;
                    if (! $groupId) {
                        $a = $assignments[$key];
                        $groupId = Group::firstOrCreate(['class_id' => $classId, 'tt_division_id' => $division->id, 'shared_key' => 'prepared-'.substr(hash('sha256', $key), 0, 24)],
                            ['name' => $a->subject->name.' · '.$a->teacher->user->name, 'entire_class' => false])->id;
                    }
                    foreach ($units as &$unit) {
                        foreach ($unit['sources'] as &$source) {
                            if ($source['key'] === $key && ! $source['group_id']) {
                                $source['group_id'] = $groupId;
                            }
                        }
                        unset($source);
                    }
                    unset($unit);
                }
            }
        }
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['preparation' => $message]);
        }
    }
}
