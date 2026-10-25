<?php

namespace App\Services\Timetable;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Tt\Division;
use App\Models\Tt\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Copy the teaching schedule; student enrolments and school assignments are separate. */
class YearRolloverService
{
    public function copy(Setting $source, array $data): Setting
    {
        return DB::transaction(function () use ($source, $data) {
            $source = Setting::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $year = AcademicYear::whereKey($data['target_year_id'])->lockForUpdate()->firstOrFail();
            $this->require($year->isOpen() && $year->id !== $source->academic_year_id, 'Choose a different, open academic year.');
            $mapping = array_map('intval', $data['mapping']);
            $sourceIds = ClassModel::where('academic_year_id', $source->academic_year_id)->pluck('id')->all();
            $this->require(! array_diff($sourceIds, array_keys($mapping)) && ! array_diff(array_keys($mapping), $sourceIds), 'Choose a destination or “Do not copy” for every source class.');
            $targets = array_filter(array_values($mapping));
            $this->require(count($targets) > 0, 'Map at least one class to the new year.');
            $this->require(count($targets) === count(array_unique($targets)), 'Choose a different destination for each class. Combining classes requires a separate timetable review.');
            $validTargets = ClassModel::where('academic_year_id', $year->id)->pluck('id')->all();
            $this->require(! array_diff($targets, $validTargets), 'Every destination must belong to the selected academic year.');

            $copy = $source->replicate();
            $copy->fill([
                'academic_year_id' => $year->id, 'name' => $data['name'], 'term_label' => $year->year_name,
                'cycle_anchor_date' => $data['cycle_anchor_date'], 'cycle_anchor_day' => $data['cycle_anchor_day'],
                'revision' => (int) Setting::where('academic_year_id', $year->id)->max('revision') + 1,
                'is_active' => false, 'is_published' => false, 'excluded_class_ids' => [],
            ]);
            $copy->preparation_version = 0;
            $copy->save();

            $references = [];
            foreach (['periods', 'breaks', 'daysdefs', 'weeksdefs', 'termsdefs', 'rooms'] as $relation) {
                foreach ($source->{$relation}()->get() as $record) {
                    $new = $record->replicate();
                    $new->tt_setting_id = $copy->id;
                    $new->save();
                    $references[$relation][$record->id] = $new->id;
                }
            }

            $divisions = $groups = $sharedKeys = $splitKeys = [];
            // Read ALL saved lessons: a final-exam exclusion applies only to the old year.
            foreach ($source->lessons()->with(['classes', 'groups.division', 'teachers', 'rooms', 'cards'])->get() as $lesson) {
                $participants = $lesson->classes->pluck('id')->merge($lesson->groups->pluck('class_id'))->unique();
                $this->require(! array_diff($participants->all(), $sourceIds), 'A lesson references a class outside the source year. Correct that lesson before copying.');
                $classIds = $participants->map(fn ($id) => $mapping[$id] ?? 0)->filter()->values()->all();
                if ($participants->isNotEmpty() && $classIds === []) {
                    continue;
                }
                $groupIds = [];
                foreach ($lesson->groups as $group) {
                    $target = $mapping[$group->class_id] ?? 0;
                    if (! $target) {
                        continue;
                    }
                    if (! isset($groups[$group->id])) {
                        $divisionId = null;
                        if ($division = $group->division) {
                            if (! isset($divisions[$division->id])) {
                                $newDivision = $division->replicate();
                                $newDivision->class_id = $target;
                                $tag = (int) Division::where('class_id', $target)->max('division_tag') + 1;
                                $this->require($tag <= 255, 'The destination class has too many divisions. Review its existing groups first.');
                                $newDivision->division_tag = $tag;
                                $newDivision->shared_key = $division->shared_key ? ($sharedKeys[$division->shared_key] ??= (string) Str::uuid()) : null;
                                $newDivision->save();
                                $divisions[$division->id] = $newDivision->id;
                            }
                            $divisionId = $divisions[$division->id];
                        }
                        $newGroup = $group->replicate();
                        $newGroup->class_id = $target;
                        $newGroup->tt_division_id = $divisionId;
                        $newGroup->shared_key = $group->shared_key ? ($sharedKeys[$group->shared_key] ??= (string) Str::uuid()) : null;
                        $newGroup->partner_id = null;
                        $newGroup->save();
                        $groups[$group->id] = $newGroup->id;
                    }
                    $groupIds[] = $groups[$group->id];
                }

                $newLesson = $lesson->replicate();
                $newLesson->tt_setting_id = $copy->id;
                foreach (['tt_daysdef_id' => 'daysdefs', 'tt_weeksdef_id' => 'weeksdefs', 'tt_termsdef_id' => 'termsdefs'] as $column => $relation) {
                    $newLesson->{$column} = $references[$relation][$lesson->{$column}] ?? null;
                }
                // Old assignment keys contain old class IDs. Copied lessons are editable drafts.
                $newLesson->preparation_key = null;
                $newLesson->assignment_sources = null;
                $newLesson->split_key = $lesson->split_key ? ($splitKeys[$lesson->split_key] ??= (string) Str::uuid()) : null;
                $newLesson->save();
                $newLesson->classes()->sync($classIds);
                $newLesson->groups()->sync($groupIds);
                $newLesson->teachers()->sync($lesson->teachers->modelKeys());
                foreach ($lesson->rooms as $room) {
                    $newLesson->rooms()->attach($references['rooms'][$room->id] ?? $room->id, ['sort_order' => $room->pivot->sort_order]);
                }
                foreach ($lesson->cards as $card) {
                    $newCard = $card->replicate();
                    $newCard->tt_lesson_id = $newLesson->id;
                    $newCard->tt_room_id = $references['rooms'][$card->tt_room_id] ?? $card->tt_room_id;
                    $newCard->locked = false;
                    $newCard->save();
                }
            }
            foreach (DB::table('tt_class_base_rooms')->where('tt_setting_id', $source->id)->get() as $baseRoom) {
                if ($target = $mapping[$baseRoom->class_id] ?? 0) {
                    DB::table('tt_class_base_rooms')->insert([
                        'tt_setting_id' => $copy->id, 'class_id' => $target,
                        'tt_room_id' => $references['rooms'][$baseRoom->tt_room_id] ?? $baseRoom->tt_room_id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            return $copy;
        });
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['mapping' => $message]);
        }
    }
}
