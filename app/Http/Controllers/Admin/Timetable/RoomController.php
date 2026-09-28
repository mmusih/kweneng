<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Services\Timetable\CardPlacementService;
use App\Services\Timetable\Conflict;
use App\Services\Timetable\ConflictChecker;
use App\Services\Timetable\GridPayload;
use App\Services\Timetable\SettingResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function __construct(
        private readonly SettingResolver $settings,
        private readonly GridPayload $payload,
        private readonly CardPlacementService $placement,
        private readonly ConflictChecker $conflicts,
    ) {}

    /** Add one room from separate fields, while retaining the bulk API for imports. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'name' => ['nullable', 'required_without:rooms_text', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'rooms_text' => ['nullable', 'required_without:name', 'string', 'max:12000'],
        ]);
        $setting = $this->setting($request);
        $entries = isset($data['name'])
            ? collect([[
                'name' => trim($data['name']),
                'short_name' => $data['short_name'] ?? null,
                'capacity' => $data['capacity'] ?? null,
            ]])
            : collect(preg_split('/\R/u', $data['rooms_text'] ?? ''))
                ->map(fn (string $line) => trim($line))
                ->filter()
                ->values()
                ->map(function (string $line) {
                    [$name, $shortName, $capacity] = array_pad(array_map('trim', explode('|', $line, 3)), 3, null);

                    return [
                        'name' => $name,
                        'short_name' => $shortName !== '' ? $shortName : null,
                        'capacity' => $capacity !== '' && $capacity !== null ? $capacity : null,
                    ];
                });

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages(['name' => 'Enter a room name.']);
        }

        $created = 0;

        DB::transaction(function () use ($entries, $setting, &$created) {
            foreach ($entries as $index => $values) {
                $validator = Validator::make($values, [
                    'name' => ['required', 'string', 'max:100', Rule::unique('tt_rooms')->where(
                        fn ($query) => $query->where('tt_setting_id', $setting->id),
                    )],
                    'short_name' => ['nullable', 'string', 'max:20'],
                    'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
                ], [], [
                    'name' => 'room name on line '.($index + 1),
                    'short_name' => 'short name on line '.($index + 1),
                    'capacity' => 'capacity on line '.($index + 1),
                ]);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                Room::create(['tt_setting_id' => $setting->id] + $validator->validated());
                $created++;
            }
        });

        return response()->json([
            'message' => $created.' '.str('room')->plural($created).' added.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $setting = $this->setting($request);
        $this->assertRoom($room, $setting);
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('tt_rooms')->where(
                fn ($query) => $query->where('tt_setting_id', $setting->id),
            )->ignore($room->id)],
            'short_name' => ['nullable', 'string', 'max:20'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $room->update($data);

        return response()->json([
            'message' => $room->name.' updated.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    public function destroy(Request $request, Room $room): JsonResponse
    {
        $setting = $this->setting($request);
        $this->assertRoom($room, $setting);

        if ($room->cards()->exists() || $room->lessons()->exists()
            || DB::table('tt_class_base_rooms')->where('tt_room_id', $room->id)->exists()) {
            throw ValidationException::withMessages([
                'room' => 'This room is in use. Remove it from lessons and baserooms before deleting it.',
            ]);
        }

        $name = $room->name;
        $room->delete();

        return response()->json([
            'message' => $name.' deleted.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    /** Replace all baseroom assignments for this timetable revision. */
    public function updateBaseRooms(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'base_rooms' => ['present', 'array'],
            'base_rooms.*' => ['nullable', 'integer'],
        ]);
        $setting = $this->setting($request);
        $classes = ClassModel::query()
            ->where('academic_year_id', $setting->academic_year_id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rooms = Room::query()
            ->where('tt_setting_id', $setting->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $assignments = [];
        $oldAssignments = DB::table('tt_class_base_rooms')
            ->where('tt_setting_id', $setting->id)
            ->pluck('tt_room_id', 'class_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($data['base_rooms'] as $classId => $roomId) {
            if ($roomId === null || $roomId === '') {
                continue;
            }

            $classId = (int) $classId;
            $roomId = (int) $roomId;

            if (! in_array($classId, $classes, true) || ! in_array($roomId, $rooms, true)) {
                throw ValidationException::withMessages(['base_rooms' => 'Every class and room must belong to this timetable.']);
            }

            if (in_array($roomId, $assignments, true)) {
                throw ValidationException::withMessages(['base_rooms' => 'A room can be the baseroom of only one class in this timetable.']);
            }

            $assignments[$classId] = $roomId;
        }

        DB::transaction(function () use ($setting, $assignments, $oldAssignments) {
            DB::table('tt_class_base_rooms')->where('tt_setting_id', $setting->id)->delete();

            foreach ($assignments as $classId => $roomId) {
                DB::table('tt_class_base_rooms')->insert([
                    'tt_setting_id' => $setting->id,
                    'class_id' => $classId,
                    'tt_room_id' => $roomId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->applyBaseRoomsToExistingCards($setting, $oldAssignments, $assignments);
        });

        return response()->json([
            'message' => count($assignments).' '.str('baseroom')->plural(count($assignments)).' saved.',
            'grid' => $this->payload->build($setting->fresh()),
        ]);
    }

    private function setting(Request $request): Setting
    {
        return $this->settings->working($request->integer('setting_id') ?: null);
    }

    private function assertRoom(Room $room, Setting $setting): void
    {
        if ((int) $room->tt_setting_id !== (int) $setting->id) {
            throw ValidationException::withMessages(['room' => 'That room belongs to another timetable revision.']);
        }
    }

    /**
     * Move roomless cards with their class when a baseroom changes. Explicit lesson or
     * per-card room choices are left alone.
     *
     * @param  array<int, int>  $oldAssignments
     * @param  array<int, int>  $assignments
     */
    private function applyBaseRoomsToExistingCards(Setting $setting, array $oldAssignments, array $assignments): void
    {
        $lessons = $setting->lessons()->with([
            'classes', 'rooms', 'cards', 'subject', 'teachers.user', 'groups', 'setting', 'weeksDef', 'termsDef',
        ])->get();

        foreach ($lessons as $lesson) {
            if ($lesson->rooms->isNotEmpty() || $lesson->classes->count() !== 1) {
                continue;
            }

            $classId = (int) $lesson->classes->first()->id;
            $oldRoomId = $oldAssignments[$classId] ?? null;
            $newRoomId = $assignments[$classId] ?? null;

            if ($oldRoomId === $newRoomId) {
                continue;
            }

            $units = $this->placement->unitsFor($lesson);
            $allLessonCardIds = collect($units)->flatMap(fn ($unit) => $unit->cardIds())->all();

            foreach ($units as $unit) {
                if ($unit->roomId() !== null && $unit->roomId() !== $oldRoomId) {
                    continue;
                }

                if ($newRoomId !== null) {
                    $roomClashes = collect($this->conflicts->check(
                        $lesson,
                        $unit->dayNumber(),
                        $unit->startPeriod(),
                        $newRoomId,
                        $allLessonCardIds,
                    ))->where('kind', Conflict::ROOM);

                    if ($roomClashes->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            'base_rooms' => $roomClashes->first()->message,
                        ]);
                    }
                }

                DB::table('tt_cards')->whereIn('id', $unit->cardIds())->update([
                    'tt_room_id' => $newRoomId,
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
