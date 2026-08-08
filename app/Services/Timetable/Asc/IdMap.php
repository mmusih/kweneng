<?php

namespace App\Services\Timetable\Asc;

use RuntimeException;

/**
 * Translates aSc's 16-character hex ids onto local primary keys, for one import run.
 *
 * Two kinds of entity live in here and the difference matters:
 *
 * - Entities with an `asc_id` column of their own (rooms, groups, lessons, the defs)
 *   could be looked up from the database, but are cached here anyway so a single run
 *   does not re-query per reference. `classroomids` on a lesson carries fifteen ids;
 *   without this, one file would issue thousands of redundant lookups.
 * - Entities with **no** `asc_id` column — `classes` and `students` are existing
 *   academic tables the rebuild does not own, and adding a column to them is out of
 *   scope — exist only in this map. It is the sole record of which aSc class is which
 *   local class, which is why the map is built before lessons are read and why a miss
 *   is an explicit failure rather than a silent null.
 *
 * The map is per-run and deliberately not persisted; §7 keeps the academic tables free
 * of timetable concerns.
 */
final class IdMap
{
    public const SUBJECT = 'subject';
    public const TEACHER = 'teacher';
    public const CLASS_ = 'class';
    public const STUDENT = 'student';
    public const ROOM = 'room';
    public const GROUP = 'group';
    public const DIVISION = 'division';
    public const LESSON = 'lesson';
    public const DAYSDEF = 'daysdef';
    public const WEEKSDEF = 'weeksdef';
    public const TERMSDEF = 'termsdef';

    /**
     * @var array<string, array<string, int>>
     */
    private array $map = [];

    public function put(string $type, string $ascId, int $localId): void
    {
        if ($ascId === '') {
            return;
        }

        $this->map[$type][$ascId] = $localId;
    }

    public function get(string $type, string $ascId): ?int
    {
        return $this->map[$type][$ascId] ?? null;
    }

    public function has(string $type, string $ascId): bool
    {
        return isset($this->map[$type][$ascId]);
    }

    /**
     * Resolve or blow up. Used where a missing id means the file is internally
     * inconsistent — a card naming a lesson that does not exist, say — as opposed to
     * an aSc entity that simply has no local counterpart, which is a report entry.
     */
    public function require(string $type, string $ascId): int
    {
        $id = $this->get($type, $ascId);

        if ($id === null) {
            throw new RuntimeException("No local id mapped for aSc {$type} [{$ascId}].");
        }

        return $id;
    }

    /**
     * Resolve a comma-separated `...ids` attribute, dropping anything unmapped.
     *
     * Order is preserved, because for `classroomids` it is a preference ranking rather
     * than a set (§5.4) and `tt_lesson_room.sort_order` stores that rank.
     *
     * @return list<int>
     */
    public function resolveList(string $type, ?string $ascIds): array
    {
        if ($ascIds === null || trim($ascIds) === '') {
            return [];
        }

        $resolved = [];

        foreach (explode(',', $ascIds) as $ascId) {
            $ascId = trim($ascId);

            if ($ascId === '') {
                continue;
            }

            $localId = $this->get($type, $ascId);

            if ($localId !== null && ! in_array($localId, $resolved, true)) {
                $resolved[] = $localId;
            }
        }

        return $resolved;
    }

    /**
     * The aSc ids in a `...ids` attribute that have no local counterpart.
     *
     * @return list<string>
     */
    public function missingFromList(string $type, ?string $ascIds): array
    {
        if ($ascIds === null || trim($ascIds) === '') {
            return [];
        }

        $missing = [];

        foreach (explode(',', $ascIds) as $ascId) {
            $ascId = trim($ascId);

            if ($ascId !== '' && ! $this->has($type, $ascId) && ! in_array($ascId, $missing, true)) {
                $missing[] = $ascId;
            }
        }

        return $missing;
    }

    public function count(string $type): int
    {
        return count($this->map[$type] ?? []);
    }

    /**
     * @return array<string, int>
     */
    public function all(string $type): array
    {
        return $this->map[$type] ?? [];
    }
}
