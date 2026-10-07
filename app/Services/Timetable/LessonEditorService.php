<?php

namespace App\Services\Timetable;

use App\Models\Tt\Card;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Safely changes a lesson without leaving its existing placements in an invalid state. */
class LessonEditorService
{
    public function __construct(
        private readonly CardPlacementService $placement = new CardPlacementService,
        private readonly ConflictChecker $checker = new ConflictChecker,
        private readonly BaseRoomResolver $baseRooms = new BaseRoomResolver,
    ) {}

    /**
     * Create an unplaced lesson. Its required cards immediately appear in the tray;
     * placement remains the grid's job so the normal conflict checks still apply.
     *
     * @param  array{subject_id:int, teacher_ids:list<int>, room_ids:list<int>, class_ids:list<int>, group_ids:list<int>, periods_per_week:float, periods_per_card:int}  $data
     */
    public function create(Setting $setting, array $data): Lesson
    {
        return DB::transaction(function () use ($setting, $data) {
            Setting::whereKey($setting->id)->lockForUpdate()->firstOrFail();
            $cardsPerCycle = $data['cards_per_cycle'] ?? null;
            $periodsPerWeek = $cardsPerCycle === null
                ? $data['periods_per_week']
                : $cardsPerCycle * $data['periods_per_card'];
            $lesson = Lesson::create([
                'tt_setting_id' => $setting->id,
                'subject_id' => $data['subject_id'],
                'periods_per_week' => $periodsPerWeek,
                'periods_per_card' => $data['periods_per_card'],
                'cards_per_cycle' => $cardsPerCycle,
            ]);

            $lesson->teachers()->sync($data['teacher_ids']);
            $lesson->rooms()->sync($this->roomPivots($data['room_ids']));
            $lesson->classes()->sync($data['class_ids']);
            $lesson->groups()->sync($data['group_ids']);

            return $lesson->fresh(['subject', 'teachers.user', 'classes', 'groups', 'rooms']);
        });
    }

    /**
     * @param  array{subject_id:int, teacher_ids:list<int>, room_ids:list<int>, class_ids:list<int>, group_ids:list<int>, periods_per_week:float, periods_per_card:int, card_id?:int|null, placement_room_id?:int|null, placement_room_mode?:string}  $data
     */
    public function update(Lesson $lesson, array $data): void
    {
        DB::transaction(function () use ($lesson, $data) {
            Setting::whereKey($lesson->tt_setting_id)->lockForUpdate()->firstOrFail();
            $lesson->loadMissing(['cards', 'setting', 'weeksDef', 'termsDef']);
            $units = $this->snapshot($lesson);
            $cardsPerCycle = $data['cards_per_cycle'] ?? null;
            $periodsPerWeek = $cardsPerCycle === null
                ? $data['periods_per_week']
                : $cardsPerCycle * $data['periods_per_card'];
            $required = $cardsPerCycle === null
                ? (int) ceil($data['periods_per_week'] / max(1, $data['periods_per_card']))
                : (int) $cardsPerCycle;

            if (count($units) > $required) {
                throw ValidationException::withMessages([
                    'periods_per_week' => 'This lesson already has '.count($units).' cards on the grid. Increase the weekly periods or return some cards to the tray first.',
                ]);
            }

            $lesson->update([
                'subject_id' => $data['subject_id'],
                'periods_per_week' => $periodsPerWeek,
                'periods_per_card' => $data['periods_per_card'],
                'cards_per_cycle' => $cardsPerCycle,
            ]);
            $lesson->teachers()->sync($data['teacher_ids']);
            $lesson->rooms()->sync($this->roomPivots($data['room_ids']));
            $lesson->classes()->sync($data['class_ids']);
            $lesson->groups()->sync($data['group_ids']);

            $lesson = $lesson->fresh([
                'subject', 'teachers.user', 'classes', 'groups', 'rooms', 'setting', 'weeksDef', 'termsDef',
            ]);

            $allOldIds = collect($units)->flatMap(fn (array $unit) => $unit['card_ids'])->all();
            $selectedCardId = $data['card_id'] ?? null;
            $placementRoomMode = $data['placement_room_mode']
                ?? (($data['placement_room_id'] ?? null) === null ? 'automatic' : 'room');
            $occupied = [];
            $baseRoomId = $this->baseRooms->forLesson($lesson);

            foreach ($units as &$unit) {
                $isSelected = $selectedCardId !== null && in_array($selectedCardId, $unit['card_ids'], true);
                $roomId = $unit['room_id'];

                if ($isSelected && (array_key_exists('placement_room_mode', $data) || array_key_exists('placement_room_id', $data))) {
                    $roomId = match ($placementRoomMode) {
                        'none' => null,
                        'room' => $data['placement_room_id'],
                        default => $data['room_ids'][0] ?? $baseRoomId,
                    };
                } elseif ($roomId !== null && ! in_array($roomId, $data['room_ids'], true)) {
                    $roomId = $data['room_ids'][0] ?? $baseRoomId;
                } elseif ($roomId === null && $data['room_ids'] === []) {
                    $roomId = $baseRoomId;
                }

                $unit['room_id'] = $roomId;

                $conflicts = $this->checker->check(
                    $lesson,
                    $unit['day'],
                    $unit['period'],
                    $roomId,
                    $allOldIds,
                );

                if ($conflicts !== []) {
                    throw ValidationException::withMessages([
                        'lesson' => $conflicts[0]->message,
                    ]);
                }

                foreach (range($unit['period'], $unit['period'] + $data['periods_per_card'] - 1) as $period) {
                    $key = $unit['days'].'|'.$unit['weeks'].'|'.$unit['terms'].'|'.$period;

                    if (isset($occupied[$key])) {
                        throw ValidationException::withMessages([
                            'periods_per_card' => 'The new duration would make two cards from this lesson overlap. Move or return one of them to the tray first.',
                        ]);
                    }

                    $occupied[$key] = true;
                }
            }
            unset($unit);

            Card::query()->where('tt_lesson_id', $lesson->id)->delete();

            foreach ($units as $unit) {
                foreach (range(0, $data['periods_per_card'] - 1) as $offset) {
                    Card::create([
                        'tt_lesson_id' => $lesson->id,
                        'period_number' => $unit['period'] + $offset,
                        'days' => $unit['days'],
                        'weeks' => $unit['weeks'],
                        'terms' => $unit['terms'],
                        'tt_room_id' => $unit['room_id'],
                        'locked' => $unit['locked'],
                    ]);
                }
            }
        });
    }

    /** @return list<array<string, mixed>> */
    private function snapshot(Lesson $lesson): array
    {
        return array_map(fn (Placement $unit) => [
            'card_ids' => $unit->cardIds(),
            'day' => $unit->dayNumber(),
            'period' => $unit->startPeriod(),
            'days' => (string) $unit->first()->days,
            'weeks' => (string) $unit->first()->weeks,
            'terms' => (string) $unit->first()->terms,
            'room_id' => $unit->roomId(),
            'locked' => $unit->isLocked(),
        ], $this->placement->unitsFor($lesson));
    }

    /** @param list<int> $ids */
    private function roomPivots(array $ids): array
    {
        $pivots = [];

        foreach (array_values($ids) as $order => $id) {
            $pivots[$id] = ['sort_order' => $order];
        }

        return $pivots;
    }
}
