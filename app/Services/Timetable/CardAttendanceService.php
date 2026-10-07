<?php

namespace App\Services\Timetable;

use App\Models\Tt\Card;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardAttendanceService
{
    public function update(Lesson $lesson, ?Placement $unit, array $classIds, array $groupIds, int $version): void
    {
        DB::transaction(function () use ($lesson, $unit, $classIds, $groupIds, $version) {
            $setting = Setting::whereKey($lesson->tt_setting_id)->lockForUpdate()->firstOrFail();
            if ($version !== (int) $setting->preparation_version) {
                throw ValidationException::withMessages(['attendance' => 'The timetable changed. Refresh before editing this card.']);
            }
            $placement = app(CardPlacementService::class);
            if ($unit?->isLocked()) {
                throw ValidationException::withMessages(['attendance' => 'Unlock this card before changing attendance.']);
            }
            if (! $unit && $placement->unplacedCount($lesson) < 1) {
                throw ValidationException::withMessages(['attendance' => 'This card is no longer in the tray. Refresh the timetable.']);
            }
            $lesson->loadMissing(['classes', 'groups', 'teachers', 'rooms']);
            $groups = \App\Models\Tt\Group::whereIn('id', $groupIds)->get();
            if ($lesson->preparation_key) {
                if (array_diff($classIds, $lesson->classes->modelKeys()) || array_diff($lesson->classes->modelKeys(), $classIds)
                    || $groups->groupBy('class_id')->contains(fn ($items) => $items->count() > 1)) {
                    throw ValidationException::withMessages(['attendance' => 'For an assignment card, choose one group or the whole class for each existing class. Change joint classes in preparation.']);
                }
                $sources = $lesson->assignment_sources;
                foreach ($sources as &$source) {
                    $classId = (int) explode(':', $source['key'])[0];
                    $source['group_id'] = $groups->firstWhere('class_id', $classId)?->id;
                }
                unset($source);
                $lesson->assignment_sources = $sources;
            } elseif ($lesson->cardsRequired() > 1) {
                // Give just this occurrence its own lesson while preserving all siblings.
                $copy = $lesson->replicate();
                $copy->cards_per_cycle = 1;
                $copy->periods_per_week = $lesson->periods_per_card;
                $copy->save();
                $copy->teachers()->sync($lesson->teachers->modelKeys());
                $copy->rooms()->sync($lesson->rooms->mapWithKeys(fn ($room) => [$room->id => ['sort_order' => $room->pivot->sort_order]])->all());
                $remaining = $lesson->cardsRequired() - 1;
                $lesson->update(['cards_per_cycle' => $remaining, 'periods_per_week' => $remaining * $lesson->periods_per_card]);
                if ($unit) Card::whereIn('id', $unit->cardIds())->update(['tt_lesson_id' => $copy->id]);
                $lesson = $copy;
            }
            $lesson->save();
            $lesson->classes()->sync($classIds);
            $lesson->groups()->sync($groupIds);
            $lesson = $lesson->fresh();

            if ($lesson->split_key) {
                foreach ($placement->linkedLessons($lesson)->where('id', '!=', $lesson->id) as $peer) {
                    foreach (array_intersect($classIds, $peer->classes->modelKeys()) as $classId) {
                        $own = $groups->where('class_id', $classId);
                        $other = $peer->groups->where('class_id', $classId);
                        if ($own->isEmpty() || $other->isEmpty() || $own->pluck('id')->intersect($other->pluck('id'))->isNotEmpty()
                            || $own->pluck('tt_division_id')->merge($other->pluck('tt_division_id'))->unique()->count() !== 1) {
                            throw ValidationException::withMessages(['attendance' => 'Cards in the same split must use different groups from one division. Unlink this card in preparation to use another split.']);
                        }
                    }
                }
            }
            if ($unit) {
                $conflicts = app(ConflictChecker::class)->check($lesson, $unit->dayNumber(), $unit->startPeriod(), $unit->roomId(), $unit->cardIds());
                if ($conflicts) throw ValidationException::withMessages(['attendance' => $conflicts[0]->message]);
            }
            $setting->increment('preparation_version');
        });
    }
}
