<?php

namespace App\Http\Controllers\Admin\Timetable;

use App\Http\Controllers\Controller;
use App\Models\Tt\Card;
use App\Models\Tt\Lesson;
use App\Models\Tt\Setting;
use App\Services\Timetable\CardPlacementService;
use App\Services\Timetable\ConflictChecker;
use App\Services\Timetable\GridPayload;
use App\Services\Timetable\Placement;
use App\Services\Timetable\PlacementRefused;
use App\Services\Timetable\SettingResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
    ) {}

    /**
     * The editor page, or its payload for a client refreshing in place.
     */
    public function index(Request $request): View|JsonResponse
    {
        $setting = $this->setting($request);

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
        [$lesson, $unit] = $this->subject($data);

        $ignore = $unit?->cardIds() ?? [];

        // A card already on the grid keeps the room it was placed in; one coming off the
        // tray has not chosen yet, so every room the lesson may use is a candidate.
        $rooms = $this->candidateRooms($lesson, $data['room_id'] ?? $unit?->roomId());

        $checker = (new ConflictChecker)->withPool(ConflictChecker::poolFor((int) $setting->id));
        $span = $checker->span($lesson);

        $periods = $setting->periods()->pluck('period_number')->map(fn ($n) => (int) $n)->all();
        $slots = [];

        foreach (range(1, max(1, (int) $setting->cycle_length)) as $day) {
            foreach ($periods as $period) {
                $slots[] = $this->verdict($checker, $lesson, $day, $period, $rooms, $ignore) + [
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
        [$lesson, $unit] = $this->subject($data);

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
    public function unplace(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_id' => ['nullable', 'integer', 'exists:tt_settings,id'],
            'card_id' => ['required', 'integer', 'exists:tt_cards,id'],
        ]);

        $setting = $this->setting($request);
        [, $unit] = $this->subject($data);

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
        [, $unit] = $this->subject($data);

        $result = $this->placement->lock($unit, (bool) $data['locked']);

        return response()->json([
            'placement' => $result->toArray(),
        ] + $this->refreshed($setting));
    }

    /**
     * The lesson being dragged, and the placement it came from if it was on the grid.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: Lesson, 1: Placement|null}
     */
    private function subject(array $data): array
    {
        if (($data['card_id'] ?? null) !== null) {
            $card = Card::query()->with('lesson')->findOrFail($data['card_id']);
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

        return [Lesson::query()->findOrFail($data['lesson_id']), null];
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
        return $rooms === [] ? [null] : $rooms;
    }

    /**
     * The whole grid, not just the card that moved.
     *
     * A single drop can change a class row's height and every lane in it — put a fourth
     * option group into a slot that held three and the row grows a line. Recomputing that
     * client-side would be a second implementation of GridPayload::assignLanes().
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
