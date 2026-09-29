<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Tt\Card;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Services\Timetable\BaseRoomResolver;
use App\Services\Timetable\CardPlacementService;
use App\Services\Timetable\ConflictChecker;
use App\Services\Timetable\GridPayload;
use App\Services\Timetable\LessonEditorService;
use App\Services\Timetable\Placement;
use App\Services\Timetable\PlacementRefused;
use App\Services\Timetable\SettingResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The drag-and-drop grid: the payload it draws, the verdict it paints with, and the moves
 * it makes.
 *
 * Nothing here trusts the browser. `candidates()` tells the client which slots are legal
 * so it can shade them red before the drop, but `move()` re-runs the same checker against
 * the database, so a client that "lets one through" — stale payload, a second admin
 * editing at the same time, or a crafted request — is still refused with the named clash.
 */
class GridController extends Controller
{
    public function __construct(
        private readonly SettingResolver $settings,
        private readonly CardPlacementService $placement,
        private readonly GridPayload $payload,
        private readonly LessonEditorService $lessonEditor,
        private readonly BaseRoomResolver $baseRooms,
    ) {}

    /**
     * The editor page, or its payload for a client refreshing in place.
     */
    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        try {
            $setting = $this->setting($request);
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return redirect()
                ->route('admin.academic-years.index')
                ->with('error', $exception->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json($this->payload->build($setting));
        }

        return view('admin.timetable.grid', [
            'setting' => $setting,
            'settings' => $this->settings->options(),
            'grid' => $this->payload->build($setting),
        ]);
    }

    /**
     * Every slot in the cycle, with a verdict — this is what paints the grid on pick-up.
     *
     * Asked once per pick-up rather than once per slot, and answered against one preloaded
     * card set: 6 days x 8 periods is 48 questions about a table small enough to hold.
     */
    public function candidates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'card_id' => ['nullable', 'integer', 'exists:tt_cards,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:tt_lessons,id'],
            'room_id' => ['nullable', 'integer', 'exists:tt_rooms,id'],
        ]);

        $setting = $this->setting($request);
        [$lesson, $unit] = $this->subject($data, $setting);
        $this->assertRoom($lesson, $data['room_id'] ?? null);

        $ignore = $unit?->cardIds() ?? [];

        // A card already on the grid keeps the room it was placed in; one coming off the
        // tray has not chosen yet, so every room the lesson may use is a candidate.
        $rooms = $unit === null
            ? $this->candidateRooms($lesson, $data['room_id'] ?? null)
            : [$data['room_id'] ?? $unit->roomId()];

        $pool = ConflictChecker::poolFor((int) $setting->id);
        $checker = (new ConflictChecker)->withPool($pool);
        $linked = $lesson->split_key ? $this->placement->linkedLessons($lesson) : collect();
        $span = $checker->span($lesson);

        $periods = $setting->periods()->pluck('period_number')->map(fn ($n) => (int) $n)->all();
        $slots = [];

        foreach (range(1, max(1, (int) $setting->cycle_length)) as $day) {
            foreach ($periods as $period) {
                $verdict = $lesson->split_key
                    ? $this->placement->splitVerdict($linked, $pool, $lesson, $day, $period, $data['room_id'] ?? null, $unit !== null)
                    : $this->verdict($checker, $lesson, $day, $period, $rooms, $ignore);
                $slots[] = $verdict + [
                    'day' => $day,
                    'period' => $period,
                ];
            }
        }

        return response()->json([
            'lesson_id' => (int) $lesson->id,
            'card_ids' => $ignore,
            'span' => $span,
            'slots' => $slots,
        ]);
    }

    /**
     * Place a tray card, or move one already on the grid.
     */
    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'card_id' => ['nullable', 'integer', 'exists:tt_cards,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:tt_lessons,id'],
            'room_id' => ['nullable', 'integer', 'exists:tt_rooms,id'],
            'day' => ['required', 'integer', 'min:1', 'max:14'],
            'period' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $setting = $this->setting($request);
        $this->assertDay($setting, $data['day']);
        [$lesson, $unit] = $this->subject($data, $setting);
        $this->assertRoom($lesson, $data['room_id'] ?? null);

        try {
            $result = $unit === null
                ? $this->placement->place($lesson, $data['day'], $data['period'], $data['room_id'] ?? null)
                : $this->placement->move($unit, $data['day'], $data['period'], $data['room_id'] ?? null);
        } catch (PlacementRefused $refused) {
            return $this->refusal($refused);
        }

        return response()->json([
            'placement' => $result->toArray(),
        ] + $this->refreshed($setting));
    }

    /**
     * Send a card back to the tray. Both rows of a double go.
     */
    public function changeRoom(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'card_id' => ['required', 'integer', 'exists:tt_cards,id'],
            'room_id' => ['present', 'nullable', 'integer', 'exists:tt_rooms,id'],
        ]);
        $setting = $this->setting($request);
        if ($data['room_id'] !== null && ! Room::where('tt_setting_id', $setting->id)->whereKey($data['room_id'])->exists()) {
            throw ValidationException::withMessages(['room_id' => 'Choose a room from this timetable.']);
        }
        [, $unit] = $this->subject($data, $setting);

        try {
            $this->placement->changeRoom($unit, $data['room_id']);
        } catch (PlacementRefused $refused) {
            return $this->refusal($refused);
        }

        return response()->json($this->refreshed($setting));
    }

    public function unplace(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'card_id' => ['required', 'integer', 'exists:tt_cards,id'],
        ]);

        $setting = $this->setting($request);
        [, $unit] = $this->subject($data, $setting);

        try {
            $this->placement->unplace($unit);
        } catch (PlacementRefused $refused) {
            return $this->refusal($refused);
        }

        return response()->json($this->refreshed($setting));
    }

    /**
     * Pin a card so a later drag cannot move it by accident.
     */
    public function lock(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'card_id' => ['required', 'integer', 'exists:tt_cards,id'],
            'locked' => ['required', 'boolean'],
        ]);

        $setting = $this->setting($request);
        [, $unit] = $this->subject($data, $setting);

        $result = $this->placement->lock($unit, (bool) $data['locked']);

        return response()->json([
            'placement' => $result->toArray(),
        ] + $this->refreshed($setting));
    }

    /** Create lesson cards from the school's existing class, subject and teacher records. */
    public function storeLesson(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_ids' => ['present', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', 'exists:teachers,id'],
            'room_ids' => ['present', 'array'],
            'room_ids.*' => ['integer', 'distinct', 'exists:tt_rooms,id'],
            'periods_per_week' => ['required', 'numeric', 'min:1', 'max:40'],
            'periods_per_card' => ['required', 'integer', 'min:1', 'max:4'],
            'cards_per_cycle' => ['nullable', 'integer', 'min:1', 'max:40'],
            'attendance' => ['required', 'array', 'min:1', 'max:12'],
            'attendance.*.class_id' => ['required', 'integer', 'exists:classes,id'],
            'attendance.*.group_id' => ['nullable', 'integer', 'distinct', 'exists:tt_groups,id'],
        ]);

        $setting = $this->setting($request);
        [$classIds, $groupIds] = $this->attendance($data['attendance'], $setting);
        $roomIds = array_map('intval', $data['room_ids']);
        $this->assertRoomsInSetting($roomIds, $setting);
        $this->assertSubjectAvailableToClasses((int) $data['subject_id'], $classIds, $setting);

        $this->lessonEditor->create($setting, [
            'subject_id' => (int) $data['subject_id'],
            'teacher_ids' => array_map('intval', $data['teacher_ids']),
            'room_ids' => $roomIds,
            'periods_per_week' => (float) $data['periods_per_week'],
            'periods_per_card' => (int) $data['periods_per_card'],
            'cards_per_cycle' => isset($data['cards_per_cycle']) ? (int) $data['cards_per_cycle'] : null,
            'class_ids' => $classIds,
            'group_ids' => $groupIds,
        ]);

        return response()->json(['message' => 'Lesson cards added to the tray.'] + $this->refreshed($setting));
    }

    /** Change the lesson behind a card, while rechecking every existing placement. */
    public function updateLesson(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'lesson_id' => ['required', 'integer', 'exists:tt_lessons,id'],
            'card_id' => ['nullable', 'integer', 'exists:tt_cards,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_ids' => ['present', 'array'],
            'teacher_ids.*' => ['integer', 'distinct', 'exists:teachers,id'],
            'room_ids' => ['present', 'array'],
            'room_ids.*' => ['integer', 'distinct', 'exists:tt_rooms,id'],
            'placement_room_id' => ['nullable', 'integer', 'exists:tt_rooms,id'],
            'placement_room_mode' => ['sometimes', 'string', 'in:automatic,none,room'],
            'periods_per_week' => ['required', 'numeric', 'min:1', 'max:40'],
            'periods_per_card' => ['required', 'integer', 'min:1', 'max:4'],
            'cards_per_cycle' => ['nullable', 'integer', 'min:1', 'max:40'],
            'attendance' => ['sometimes', 'array', 'min:1', 'max:12'],
            'attendance.*.class_id' => ['required', 'integer', 'exists:classes,id'],
            'attendance.*.group_id' => ['nullable', 'integer', 'distinct', 'exists:tt_groups,id'],
        ]);

        $setting = $this->setting($request);
        $lesson = $this->editableLesson($data, $setting);
        $roomIds = array_map('intval', $data['room_ids']);
        $placementRoomMode = $data['placement_room_mode']
            ?? (($data['placement_room_id'] ?? null) === null ? 'automatic' : 'room');

        if ($placementRoomMode === 'room' && ($data['placement_room_id'] ?? null) === null) {
            throw ValidationException::withMessages(['placement_room_id' => 'Choose the room for this card.']);
        }

        if ($placementRoomMode === 'room' && ! in_array((int) $data['placement_room_id'], $roomIds, true)) {
            $roomIds[] = (int) $data['placement_room_id'];
        }

        [$classIds, $groupIds] = array_key_exists('attendance', $data)
            ? $this->attendance($data['attendance'], $setting)
            : [
                $lesson->classes()->pluck('classes.id')->map(fn ($id) => (int) $id)->all(),
                $lesson->groups()->pluck('tt_groups.id')->map(fn ($id) => (int) $id)->all(),
            ];

        $this->assertRoomsInSetting($roomIds, $setting);

        $this->lessonEditor->update($lesson, [
            'subject_id' => (int) $data['subject_id'],
            'teacher_ids' => array_map('intval', $data['teacher_ids']),
            'room_ids' => $roomIds,
            'periods_per_week' => (float) $data['periods_per_week'],
            'periods_per_card' => (int) $data['periods_per_card'],
            'cards_per_cycle' => isset($data['cards_per_cycle']) ? (int) $data['cards_per_cycle'] : null,
            'class_ids' => $classIds,
            'group_ids' => $groupIds,
            'card_id' => isset($data['card_id']) ? (int) $data['card_id'] : null,
            'placement_room_id' => $placementRoomMode === 'room' ? (int) $data['placement_room_id'] : null,
            'placement_room_mode' => $placementRoomMode,
        ]);

        return response()->json(['message' => 'Lesson updated.'] + $this->refreshed($setting));
    }

    /** @param list<int> $roomIds */
    private function assertRoomsInSetting(array $roomIds, Setting $setting): void
    {
        if (Room::query()->whereIn('id', $roomIds)->where('tt_setting_id', '!=', $setting->id)->exists()) {
            throw ValidationException::withMessages(['room_ids' => 'Every room must belong to this timetable revision.']);
        }
    }

    /**
     * When the school has curriculum records for a class, use them as authority. A class
     * with no setup records remains usable so an incomplete initial setup is not a dead end.
     *
     * @param  list<int>  $classIds
     */
    private function assertSubjectAvailableToClasses(int $subjectId, array $classIds, Setting $setting): void
    {
        foreach ($classIds as $classId) {
            $knownSubjectIds = collect(['class_subjects', 'teacher_subjects', 'student_subjects'])
                ->flatMap(fn (string $table) => DB::table($table)
                    ->where('academic_year_id', $setting->academic_year_id)
                    ->where('class_id', $classId)
                    ->pluck('subject_id'))
                ->map(fn ($id) => (int) $id)
                ->unique();

            if ($knownSubjectIds->isNotEmpty() && ! $knownSubjectIds->contains($subjectId)) {
                $className = ClassModel::query()->whereKey($classId)->value('name') ?? 'The selected class';

                throw ValidationException::withMessages([
                    'subject_id' => $className.' is not assigned this subject in the school setup.',
                ]);
            }
        }
    }

    /**
     * @param  list<array{class_id:int, group_id?:int|null}>  $rows
     * @return array{0:list<int>, 1:list<int>}
     */
    private function attendance(array $rows, Setting $setting): array
    {
        $classIds = collect($rows)
            ->pluck('class_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $validClassCount = ClassModel::query()
            ->where('academic_year_id', $setting->academic_year_id)
            ->whereIn('id', $classIds)
            ->count();

        if ($validClassCount !== count($classIds)) {
            throw ValidationException::withMessages([
                'attendance' => 'Every attending class must belong to this timetable’s academic year.',
            ]);
        }

        $groupIds = [];
        $groupsByClass = [];
        $wholeClasses = [];

        foreach ($rows as $index => $row) {
            if (($row['group_id'] ?? null) === null) {
                $wholeClasses[(int) $row['class_id']] = true;

                continue;
            }

            $group = Group::query()->findOrFail((int) $row['group_id']);

            if ((int) $group->class_id !== (int) $row['class_id'] || $group->entire_class) {
                throw ValidationException::withMessages([
                    "attendance.{$index}.group_id" => 'Choose a division group belonging to the selected class.',
                ]);
            }

            $groupIds[] = (int) $group->id;
            $groupsByClass[(int) $row['class_id']][] = $group;
        }

        foreach ($classIds as $classId) {
            $groups = collect($groupsByClass[$classId] ?? []);

            if (isset($wholeClasses[$classId]) && $groups->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'attendance' => 'Choose either the entire class or its division groups, not both.',
                ]);
            }

            if ($groups->pluck('tt_division_id')->filter()->unique()->count() > 1) {
                throw ValidationException::withMessages([
                    'attendance' => 'Groups from the same class must belong to one division.',
                ]);
            }
        }

        return [$classIds, $groupIds];
    }

    /** Make an unplaced copy with the same class, groups, teachers and room choices. */
    public function duplicateLesson(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'lesson_id' => ['required', 'integer', 'exists:tt_lessons,id'],
        ]);
        $setting = $this->setting($request);
        $lesson = $this->editableLesson($data, $setting);

        $copy = $lesson->replicate(['asc_id']);
        $copy->asc_id = null;
        $copy->save();
        $copy->teachers()->sync($lesson->teachers()->pluck('teachers.id'));
        $copy->classes()->sync($lesson->classes()->pluck('classes.id'));
        $copy->groups()->sync($lesson->groups()->pluck('tt_groups.id'));
        $copy->rooms()->sync($lesson->rooms()->get()->mapWithKeys(
            fn ($room) => [$room->id => ['sort_order' => $room->pivot->sort_order]],
        )->all());

        return response()->json(['message' => 'Lesson duplicated into the tray.'] + $this->refreshed($setting));
    }

    /** Explicitly change timetable demand, keeping every placed card intact. */
    public function updateRequiredCount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['required', 'integer', 'exists:tt_settings,id'],
            'lesson_id' => ['required', 'integer', 'exists:tt_lessons,id'],
            'required' => ['required', 'integer', 'min:0', 'max:40'],
            'version' => ['required', 'integer'],
            'expected_required' => ['required', 'integer'],
            'expected_placed' => ['required', 'integer'],
        ]);
        $setting = $this->setting($request);
        DB::transaction(function () use ($setting, $data) {
            $current = Setting::whereKey($setting->id)->lockForUpdate()->firstOrFail();
            $stack = collect($this->payload->build($current)['requirements'])
                ->first(fn ($item) => in_array((int) $data['lesson_id'], $item['lesson_ids'], true));
            if (!$stack || $current->preparation_version != $data['version']
                || $stack['required'] !== $data['expected_required'] || $stack['placed'] !== $data['expected_placed']) {
                throw ValidationException::withMessages(['required' => 'This timetable changed. Close this dialog and refresh before changing the count.']);
            }
            if ($data['required'] < $stack['placed']) {
                throw ValidationException::withMessages(['required' => 'The required count cannot be less than the number already placed. Return cards to the tray first.']);
            }
            if ($stack['split_key']) {
                throw ValidationException::withMessages(['required' => 'Adjust linked split counts together in the assignment editor.']);
            }
            $lessons = Lesson::with(['cards', 'teachers', 'classes', 'groups', 'rooms'])->whereIn('id', $stack['lesson_ids'])->lockForUpdate()->get();
            $delta = $data['required'] - $stack['required'];
            if ($delta > 0) {
                $source = $lessons->first();
                foreach ($source->classes as $class) {
                    foreach ($source->teachers as $teacher) {
                        $periods = Lesson::where('tt_setting_id', $current->id)->where('subject_id', $source->subject_id)
                            ->whereHas('classes', fn ($query) => $query->where('classes.id', $class->id))
                            ->whereHas('teachers', fn ($query) => $query->where('teachers.id', $teacher->id))
                            ->get()->sum(fn ($lesson) => $lesson->cardsRequired() * $lesson->periods_per_card);
                        if ($periods + $delta * $source->periods_per_card > 40) {
                            throw ValidationException::withMessages(['required' => 'An assignment can use at most 40 periods per cycle, including its other lesson cards.']);
                        }
                    }
                }
            }
            if ($delta < 0) {
                $remaining = -$delta;
                foreach ($lessons as $lesson) {
                    $take = min($remaining, $this->placement->unplacedCount($lesson));
                    if (!$take) continue;
                    $count = $lesson->cardsRequired() - $take;
                    $lesson->update(['cards_per_cycle' => $count, 'periods_per_week' => $count * $lesson->periods_per_card]);
                    $remaining -= $take;
                }
            } elseif ($delta > 0) {
                $source = $lessons->first();
                if ($source->preparation_key === null) {
                    $count = $source->cardsRequired() + $delta;
                    $source->update(['cards_per_cycle' => $count, 'periods_per_week' => $count * $source->periods_per_card]);
                } else {
                    foreach ($lessons->filter(fn ($lesson) => $lesson->cardsRequired() === 0) as $lesson) {
                        if (!$delta) break;
                        $lesson->update(['cards_per_cycle' => 1, 'periods_per_week' => $lesson->periods_per_card]);
                        --$delta;
                    }
                    while ($delta-- > 0) {
                        $copy = $source->replicate();
                        $copy->preparation_key = (string) \Illuminate\Support\Str::uuid();
                        $copy->cards_per_cycle = 1;
                        $copy->periods_per_week = $source->periods_per_card;
                        $copy->save();
                        $copy->teachers()->sync($source->teachers->modelKeys());
                        $copy->classes()->sync($source->classes->modelKeys());
                        $copy->groups()->sync($source->groups->modelKeys());
                        $copy->rooms()->sync($source->rooms->mapWithKeys(fn ($room) => [$room->id => ['sort_order' => $room->pivot->sort_order]])->all());
                    }
                }
            }
            $current->increment('preparation_version');
        });

        return response()->json(['message' => 'Required count updated. Placed cards and teaching assignments are unchanged.'] + $this->refreshed($setting->fresh()));
    }

    /** Delete a lesson and all of its placed cards. */
    public function destroyLesson(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'lesson_id' => ['required', 'integer', 'exists:tt_lessons,id'],
        ]);
        $setting = $this->setting($request);
        $lesson = $this->editableLesson($data, $setting);
        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted.'] + $this->refreshed($setting));
    }

    /**
     * The lesson being dragged, and the placement it came from if it was on the grid.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: Lesson, 1: Placement|null}
     */
    private function subject(array $data, Setting $setting): array
    {
        if (($data['card_id'] ?? null) !== null) {
            $card = Card::query()->with('lesson')->findOrFail($data['card_id']);

            if ((int) $card->lesson?->tt_setting_id !== (int) $setting->id) {
                throw ValidationException::withMessages([
                    'card_id' => 'That card belongs to another timetable. Refresh and try again.',
                ]);
            }

            $unit = $this->placement->unitContaining($card);

            if ($unit === null) {
                throw ValidationException::withMessages([
                    'card_id' => 'That card is no longer on the grid — refresh and try again.',
                ]);
            }

            return [$unit->lesson, $unit];
        }

        if (($data['lesson_id'] ?? null) === null) {
            throw ValidationException::withMessages([
                'lesson_id' => 'Name the lesson being placed, or the card being moved.',
            ]);
        }

        $lesson = Lesson::query()->findOrFail($data['lesson_id']);

        if ((int) $lesson->tt_setting_id !== (int) $setting->id) {
            throw ValidationException::withMessages([
                'lesson_id' => 'That lesson belongs to another timetable. Refresh and try again.',
            ]);
        }

        return [$lesson, null];
    }

    /** @param array<string, mixed> $data */
    private function editableLesson(array $data, Setting $setting): Lesson
    {
        $lesson = Lesson::query()->findOrFail($data['lesson_id']);

        if ((int) $lesson->tt_setting_id !== (int) $setting->id) {
            throw ValidationException::withMessages([
                'lesson_id' => 'That lesson belongs to another timetable. Refresh and try again.',
            ]);
        }

        if (($data['card_id'] ?? null) !== null) {
            $belongs = Card::query()
                ->whereKey($data['card_id'])
                ->where('tt_lesson_id', $lesson->id)
                ->exists();

            if (! $belongs) {
                throw ValidationException::withMessages(['card_id' => 'That card does not belong to this lesson.']);
            }
        }

        if ($lesson->preparation_key !== null) {
            throw ValidationException::withMessages(['lesson' => 'This occurrence is linked to teacher assignments. Use Create cards from teacher assignments to change its pattern, attendance or split. Return placed cards to the tray first.']);
        }

        return $lesson;
    }

    /**
     * The request validator knows the application's broad limit; the selected setting
     * knows the real one. Reject a stale or crafted day before Bitmask has to throw.
     */
    private function assertDay(Setting $setting, int $day): void
    {
        if ($day <= max(1, (int) $setting->cycle_length)) {
            return;
        }

        throw ValidationException::withMessages([
            'day' => "Day {$day} is outside this timetable's cycle.",
        ]);
    }

    /** A client may choose among the lesson's candidate rooms, never invent one. */
    private function assertRoom(Lesson $lesson, ?int $roomId): void
    {
        if ($roomId === null || $lesson->rooms()->whereKey($roomId)->exists()
            || $this->baseRooms->forLesson($lesson) === $roomId) {
            return;
        }

        throw ValidationException::withMessages([
            'room_id' => 'That room is not available to this lesson.',
        ]);
    }

    /**
     * Is this slot legal, and if not, why — mirroring how the service picks a room, so the
     * shading and the drop agree.
     *
     * @param  list<int|null>  $rooms
     * @param  list<int>  $ignore
     * @return array{ok: bool, room_id: int|null, conflicts: list<array<string, mixed>>}
     */
    private function verdict(
        ConflictChecker $checker,
        Lesson $lesson,
        int $day,
        int $period,
        array $rooms,
        array $ignore,
    ): array {
        $first = null;

        foreach ($rooms as $roomId) {
            $conflicts = $checker->check($lesson, $day, $period, $roomId, $ignore);

            if ($conflicts === []) {
                return ['ok' => true, 'room_id' => $roomId, 'conflicts' => []];
            }

            $first ??= ['room_id' => $roomId, 'conflicts' => $conflicts];
        }

        return [
            'ok' => false,
            'room_id' => $first['room_id'] ?? null,
            'conflicts' => array_map(fn ($c) => $c->toArray(), $first['conflicts'] ?? []),
        ];
    }

    /**
     * @return list<int|null>
     */
    private function candidateRooms(Lesson $lesson, ?int $roomId): array
    {
        if ($roomId !== null) {
            return [$roomId];
        }

        $rooms = $lesson->rooms()->pluck('tt_rooms.id')->map(fn ($id) => (int) $id)->all();

        // A lesson with no room named is not roomless-illegal; it simply has no room rule.
        if ($rooms === []) {
            return [$this->baseRooms->forLesson($lesson)];
        }

        return $rooms;
    }

    /**
     * The whole grid, not just the card that moved.
     *
     * Return the authoritative grid after every write so every open editor sees the
     * saved cards, tray counts, rooms and lesson details in one consistent payload.
     *
     * @return array{grid: array<string, mixed>}
     */
    private function refreshed(Setting $setting): array
    {
        return ['grid' => $this->payload->build($setting->fresh())];
    }

    private function refusal(PlacementRefused $refused): JsonResponse
    {
        return response()->json([
            'message' => $refused->getMessage(),
            'conflicts' => $refused->toArray(),
        ], 422);
    }

    private function setting(Request $request): Setting
    {
        return $this->settings->working(
            $request->integer('setting_id') ?: $request->integer('setting') ?: null,
        );
    }
}
