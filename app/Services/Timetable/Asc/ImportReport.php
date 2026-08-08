<?php

namespace App\Services\Timetable\Asc;

/**
 * What one import run did, and what it could not do.
 *
 * The plan (§Phase 2) is explicit that unmatched aSc entities are *reported*, never
 * silently created: the school's `subjects` table has 20 rows against the XML's 31, and
 * quietly inserting the other 11 would fork the subject list and corrupt marks entry
 * downstream. So the importer's real output is this report, and the row counts are
 * secondary.
 *
 * `emptyGroups` is the other half of that contract. Group membership is derived from
 * `student_subjects`, not from the file (§5.3), so a group that resolves to zero
 * students means the elections are wrong — not that the group is empty. The admin needs
 * that list to fix the data.
 */
final class ImportReport
{
    /**
     * @var array<string, array{created: int, updated: int, matched: int, skipped: int}>
     */
    private array $counts = [];

    /**
     * @var list<array{type: string, asc_id: string, name: string, reason: string}>
     */
    private array $unmatched = [];

    /**
     * @var list<array{group: string, class: string, subject: ?string, teacher: ?string}>
     */
    private array $emptyGroups = [];

    /**
     * @var list<string>
     */
    private array $warnings = [];

    public function __construct(
        public readonly string $sourcePath = '',
        public readonly bool $dryRun = false,
    ) {}

    private ?int $settingId = null;

    public function created(string $type, int $n = 1): void
    {
        $this->bump($type, 'created', $n);
    }

    public function updated(string $type, int $n = 1): void
    {
        $this->bump($type, 'updated', $n);
    }

    /** An aSc entity resolved onto an existing local row. */
    public function matched(string $type, int $n = 1): void
    {
        $this->bump($type, 'matched', $n);
    }

    /** Present in the file but deliberately not imported. */
    public function skipped(string $type, int $n = 1): void
    {
        $this->bump($type, 'skipped', $n);
    }

    private function bump(string $type, string $key, int $n): void
    {
        $this->counts[$type] ??= ['created' => 0, 'updated' => 0, 'matched' => 0, 'skipped' => 0];
        $this->counts[$type][$key] += $n;
    }

    public function unmatched(string $type, string $ascId, string $name, string $reason): void
    {
        $this->unmatched[] = compact('type', 'name', 'reason') + ['asc_id' => $ascId];
        $this->skipped($type);
    }

    public function emptyGroup(string $group, string $class, ?string $subject, ?string $teacher): void
    {
        $this->emptyGroups[] = compact('group', 'class', 'subject', 'teacher');
    }

    public function warn(string $message): void
    {
        if (! in_array($message, $this->warnings, true)) {
            $this->warnings[] = $message;
        }
    }

    public function setSettingId(?int $id): void
    {
        $this->settingId = $id;
    }

    public function settingId(): ?int
    {
        return $this->settingId;
    }

    /**
     * @return array<string, array{created: int, updated: int, matched: int, skipped: int}>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    public function countOf(string $type, string $key): int
    {
        return $this->counts[$type][$key] ?? 0;
    }

    /**
     * @return list<array{type: string, asc_id: string, name: string, reason: string}>
     */
    public function unmatchedEntities(): array
    {
        return $this->unmatched;
    }

    /**
     * @return list<array{type: string, asc_id: string, name: string, reason: string}>
     */
    public function unmatchedOfType(string $type): array
    {
        return array_values(array_filter(
            $this->unmatched,
            static fn (array $row): bool => $row['type'] === $type,
        ));
    }

    /**
     * @return list<array{group: string, class: string, subject: ?string, teacher: ?string}>
     */
    public function emptyGroups(): array
    {
        return $this->emptyGroups;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /** "Clean" is the Phase 2 acceptance bar: nothing in the file went unresolved. */
    public function isClean(): bool
    {
        return $this->unmatched === [] && $this->emptyGroups === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->sourcePath,
            'dry_run' => $this->dryRun,
            'setting_id' => $this->settingId,
            'counts' => $this->counts,
            'unmatched' => $this->unmatched,
            'empty_groups' => $this->emptyGroups,
            'warnings' => $this->warnings,
        ];
    }
}
