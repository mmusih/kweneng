<?php

namespace App\Services\Timetable\Asc;

use App\Models\AcademicYear;
use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Card;
use App\Models\Tt\DaysDef;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Period;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Models\Tt\TermsDef;
use App\Models\Tt\WeeksDef;
use App\Services\Timetable\GroupMembershipResolver;
use App\Support\Timetable\Bitmask;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;

/**
 * Reads an aSc TimeTables XML export into the tt_* schema.
 *
 * Each run creates a NEW `tt_settings` row, inactive and unpublished, rather than
 * editing one in place. The school cuts a separate aSc file per term and re-exports as
 * it works (the sample is "Rev 5"), so an import is a snapshot, not an edit — and a
 * snapshot that lands as a draft can never disturb the timetable currently on screen.
 * Everything setting-scoped therefore starts clean; only the entities that hang off
 * academic tables instead (`tt_divisions`, `tt_groups`, and the two meta tables) have
 * to be idempotent, and those key on `asc_id` or a unique pair.
 *
 * Nothing here creates a subject, teacher, class or student. See EntityMatcher for why.
 */
final class XmlImporter
{
    private IdMap $ids;

    private ImportReport $report;

    private EntityMatcher $matcher;

    private AscOptions $options;

    private Setting $setting;

    private GroupMembershipResolver $membership;

    private int $cycleLength = 6;

    /**
     * @param  array{dry_run?: bool, academic_year_id?: int|null, name?: string|null, term_label?: string|null}  $opts
     */
    public function import(string $path, array $opts = []): ImportReport
    {
        $dryRun = (bool) ($opts['dry_run'] ?? false);

        $xml = $this->load($path);

        $this->options = AscOptions::fromRoot($xml);
        $this->report = new ImportReport($path, $dryRun);
        $this->ids = new IdMap;
        $this->matcher = new EntityMatcher;
        $this->membership = new GroupMembershipResolver;

        try {
            DB::transaction(function () use ($xml, $opts, $dryRun): void {
                $this->importSetting($xml, $opts);
                $this->importPeriods($xml);
                $this->importBreaks($xml);
                $this->importDefs($xml);
                $this->importSubjects($xml);
                $this->importTeachers($xml);
                $this->importRooms($xml);
                $this->importClasses($xml);
                $this->importStudents($xml);
                $this->importGroups($xml);
                $this->importLessons($xml);
                $this->importCards($xml);
                $this->reportDerivedMembership();

                if ($dryRun) {
                    // The only way to measure a real import is to perform one. Every
                    // count and every unmatched entity below came from actual writes;
                    // this unwinds them once they have been counted.
                    throw new DryRunComplete;
                }
            });
        } catch (DryRunComplete) {
            $this->report->setSettingId(null);
        }

        return $this->report;
    }

    // -----------------------------------------------------------------
    // Loading
    // -----------------------------------------------------------------

    /**
     * aSc writes windows-1252 (§5), so the bytes are transcoded before libxml sees
     * them and the declaration is rewritten to match. Leaving the original declaration
     * on transcoded bytes would make libxml decode a second time and mangle every
     * accented name in the file.
     */
    private function load(string $path): SimpleXMLElement
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("aSc export not readable at [{$path}].");
        }

        $raw = file_get_contents($path);

        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException("aSc export at [{$path}] is empty or unreadable.");
        }

        $declared = 'UTF-8';

        if (preg_match('/<\?xml[^>]*encoding=["\']([^"\']+)["\']/i', substr($raw, 0, 512), $m) === 1) {
            $declared = strtoupper(trim($m[1]));
        }

        if ($declared !== 'UTF-8' && $declared !== 'UTF8') {
            $raw = $this->toUtf8($raw, $declared);
            $raw = preg_replace(
                '/(<\?xml[^>]*encoding=["\'])[^"\']+(["\'])/i',
                '${1}UTF-8${2}',
                $raw,
                1,
            ) ?? $raw;
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string($raw);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $first = $errors[0]->message ?? 'unknown parse error';

            throw new RuntimeException("aSc export at [{$path}] is not valid XML: ".trim($first));
        }

        if ($xml->getName() !== 'timetable') {
            throw new RuntimeException(
                "Expected a <timetable> root in [{$path}], found <{$xml->getName()}>.",
            );
        }

        return $xml;
    }

    private function toUtf8(string $raw, string $from): string
    {
        $converted = @mb_convert_encoding($raw, 'UTF-8', $from);

        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        $converted = @iconv($from, 'UTF-8//TRANSLIT', $raw);

        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        throw new RuntimeException("Could not transcode the aSc export from [{$from}] to UTF-8.");
    }

    // -----------------------------------------------------------------
    // Sections
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $opts
     */
    private function importSetting(SimpleXMLElement $xml, array $opts): void
    {
        $yearId = $opts['academic_year_id']
            ?? AcademicYear::query()->where('active', true)->value('id')
            ?? AcademicYear::query()->value('id');

        if ($yearId === null) {
            throw new RuntimeException(
                'No academic year exists to attach the imported timetable to. Create one first.',
            );
        }

        $this->cycleLength = $this->detectCycleLength($xml);

        $revision = (int) Setting::query()->where('academic_year_id', $yearId)->max('revision') + 1;

        $this->setting = Setting::create([
            'academic_year_id' => $yearId,
            'name' => $opts['name'] ?? pathinfo($this->report->sourcePath, PATHINFO_FILENAME),
            'term_label' => $opts['term_label'] ?? null,
            'revision' => $revision,
            'cycle_length' => $this->cycleLength,
            'asc_options' => $this->options->toJson(),
            // A fresh import is always a draft. Publishing is a separate, deliberate act.
            'is_active' => false,
            'is_published' => false,
        ]);

        $this->report->setSettingId($this->setting->id);
        $this->report->created('settings');
    }

    /**
     * The cycle length is the width of the day masks, read from the file rather than
     * assumed: this school runs six days, but the same importer must not corrupt a
     * five-day file by padding every mask with a phantom Saturday.
     */
    private function detectCycleLength(SimpleXMLElement $xml): int
    {
        $widths = [];

        foreach ($xml->daysdefs->daysdef ?? [] as $def) {
            foreach (explode(',', (string) self::attr($def, 'days')) as $mask) {
                $mask = trim($mask);

                if ($mask !== '') {
                    $widths[] = strlen($mask);
                }
            }
        }

        foreach ($xml->cards->card ?? [] as $card) {
            $mask = trim((string) self::attr($card, 'days'));

            if ($mask !== '') {
                $widths[] = strlen($mask);
            }
        }

        if ($widths === []) {
            $this->report->warn('No day masks in the file; assuming a 6-day cycle.');

            return 6;
        }

        if (count(array_unique($widths)) > 1) {
            $this->report->warn(sprintf(
                'Day masks disagree on width (%s); using the widest.',
                implode(', ', array_unique($widths)),
            ));
        }

        return max($widths);
    }

    private function importPeriods(SimpleXMLElement $xml): void
    {
        foreach ($xml->periods->period ?? [] as $p) {
            $number = (int) self::attr($p, 'period');

            if ($number < 1) {
                $this->report->warn('Skipped a <period> with no usable period number.');

                continue;
            }

            Period::create([
                'tt_setting_id' => $this->setting->id,
                'period_number' => $number,
                'name' => self::str($p, 'name') ?? (string) $number,
                'short_name' => self::str($p, 'short'),
                'start_time' => self::time(self::attr($p, 'starttime')),
                'end_time' => self::time(self::attr($p, 'endtime')),
            ]);

            $this->report->created('periods');
        }
    }

    /**
     * aSc's `break="5"` names the period the break comes *before*; `after_period` names
     * the one it comes *after*. The sample's single break runs 10:10–10:30, between
     * period 4 (ends 10:10) and period 5 (starts 10:30), which settles the off-by-one:
     * `break="5"` stores as `after_period = 4`.
     */
    private function importBreaks(SimpleXMLElement $xml): void
    {
        foreach ($xml->breaks->break ?? [] as $b) {
            $before = (int) self::attr($b, 'break');

            BreakPeriod::create([
                'tt_setting_id' => $this->setting->id,
                'name' => self::str($b, 'name') ?? 'Break',
                'short_name' => self::str($b, 'short'),
                'start_time' => self::time(self::attr($b, 'starttime')),
                'end_time' => self::time(self::attr($b, 'endtime')),
                'after_period' => $before > 0 ? $before - 1 : null,
                'days' => self::str($b, 'days'),
                'asc_id' => self::str($b, 'id'),
            ]);

            $this->report->created('breaks');
        }
    }

    /**
     * Day, week and term definitions.
     *
     * The mask payload is stored in aSc's own comma-separated form ("100000,010000,…"),
     * per §7's "bitmasks as aSc-style strings". Consumers must split on the comma before
     * constructing a Bitmask — the value object strips non-01 characters, so handing it
     * the joined string would silently concatenate six masks into one.
     */
    private function importDefs(SimpleXMLElement $xml): void
    {
        foreach ($xml->daysdefs->daysdef ?? [] as $def) {
            $model = DaysDef::create([
                'tt_setting_id' => $this->setting->id,
                'name' => self::str($def, 'name') ?? 'Days',
                'short_name' => self::str($def, 'short'),
                'days' => self::maskList(self::attr($def, 'days'), $this->cycleLength),
                'asc_id' => self::str($def, 'id'),
            ]);

            $this->ids->put(IdMap::DAYSDEF, (string) self::attr($def, 'id'), $model->id);
            $this->report->created('daysdefs');
        }

        foreach ($xml->weeksdefs->weeksdef ?? [] as $def) {
            $model = WeeksDef::create([
                'tt_setting_id' => $this->setting->id,
                'name' => self::str($def, 'name') ?? 'Weeks',
                'short_name' => self::str($def, 'short'),
                'weeks' => self::str($def, 'weeks') ?? '1',
                'asc_id' => self::str($def, 'id'),
            ]);

            $this->ids->put(IdMap::WEEKSDEF, (string) self::attr($def, 'id'), $model->id);
            $this->report->created('weeksdefs');
        }

        foreach ($xml->termsdefs->termsdef ?? [] as $def) {
            $model = TermsDef::create([
                'tt_setting_id' => $this->setting->id,
                'name' => self::str($def, 'name') ?? 'Terms',
                'short_name' => self::str($def, 'short'),
                'terms' => self::str($def, 'terms') ?? '1',
                'asc_id' => self::str($def, 'id'),
            ]);

            $this->ids->put(IdMap::TERMSDEF, (string) self::attr($def, 'id'), $model->id);
            $this->report->created('termsdefs');
        }
    }

    private function importSubjects(SimpleXMLElement $xml): void
    {
        foreach ($xml->subjects->subject ?? [] as $s) {
            $ascId = (string) self::attr($s, 'id');
            $name = self::str($s, 'name') ?? '';
            $short = self::str($s, 'short');

            $subject = $this->matcher->subject($name, $short);

            if ($subject === null) {
                $this->report->unmatched(
                    'subjects',
                    $ascId,
                    $name,
                    'No subject matches this code or name; lessons using it were skipped.',
                );

                continue;
            }

            $this->ids->put(IdMap::SUBJECT, $ascId, $subject->id);

            $this->upsertMeta('tt_subject_meta', ['subject_id' => $subject->id], [
                'short_name' => self::truncate($short, 20),
                'colour' => self::str($s, 'color'),
                'asc_id' => $ascId,
            ]);

            $this->report->matched('subjects');
        }
    }

    private function importTeachers(SimpleXMLElement $xml): void
    {
        foreach ($xml->teachers->teacher ?? [] as $t) {
            $ascId = (string) self::attr($t, 'id');
            $name = self::str($t, 'name') ?? '';

            $teacher = $this->matcher->teacher($name, self::str($t, 'lastname'));

            if ($teacher === null) {
                $this->report->unmatched(
                    'teachers',
                    $ascId,
                    $name,
                    'No teacher matches this name; lessons using them were imported without a teacher.',
                );

                continue;
            }

            $this->ids->put(IdMap::TEACHER, $ascId, $teacher->id);

            $this->upsertMeta('tt_teacher_meta', ['teacher_id' => $teacher->id], [
                'short_name' => self::truncate(self::str($t, 'short'), 20),
                'colour' => self::str($t, 'color'),
                'asc_id' => $ascId,
            ]);

            $this->report->matched('teachers');
        }
    }

    /**
     * Rooms are the one physical entity the rebuild owns outright — there is no legacy
     * room table to match against — so these are created rather than matched.
     */
    private function importRooms(SimpleXMLElement $xml): void
    {
        foreach ($xml->classrooms->classroom ?? [] as $r) {
            $ascId = (string) self::attr($r, 'id');

            $room = Room::create([
                'tt_setting_id' => $this->setting->id,
                'name' => self::str($r, 'name') ?? 'Room',
                'short_name' => self::truncate(self::str($r, 'short'), 20),
                'capacity' => self::capacity(self::attr($r, 'capacity')),
                'asc_id' => $ascId,
            ]);

            $this->ids->put(IdMap::ROOM, $ascId, $room->id);
            $this->report->created('rooms');
        }
    }

    private function importClasses(SimpleXMLElement $xml): void
    {
        foreach ($xml->classes->class ?? [] as $c) {
            $ascId = (string) self::attr($c, 'id');
            $name = self::str($c, 'name') ?? '';

            $class = $this->matcher->class($name, self::str($c, 'short'));

            if ($class === null) {
                $this->report->unmatched(
                    'classes',
                    $ascId,
                    $name,
                    'No class matches this name; its groups and lessons were skipped.',
                );

                continue;
            }

            $this->ids->put(IdMap::CLASS_, $ascId, $class->id);
            $this->report->matched('classes');
        }
    }

    /**
     * Students are mapped but nothing is written for them.
     *
     * Membership is derived from `student_subjects` at read time (§5.3), so the import
     * has no roster to store. The map exists so that a group carrying an explicit
     * `studentids` override could be resolved, and so unmatched students are reported.
     */
    private function importStudents(SimpleXMLElement $xml): void
    {
        foreach ($xml->students->student ?? [] as $s) {
            $ascId = (string) self::attr($s, 'id');
            $name = self::str($s, 'name') ?? '';
            $classId = $this->ids->get(IdMap::CLASS_, (string) self::attr($s, 'classid'));

            $student = $this->matcher->student($name, $classId);

            if ($student === null) {
                $this->report->unmatched(
                    'students',
                    $ascId,
                    $name,
                    $classId === null
                        ? 'Their class did not match, so the student could not be resolved.'
                        : 'No student with this name in the matched class.',
                );

                continue;
            }

            $this->ids->put(IdMap::STUDENT, $ascId, $student->id);
            $this->report->matched('students');
        }
    }

    /**
     * Divisions and groups.
     *
     * `divisiontag="0"` is aSc's whole-class sentinel, not a division: those groups get
     * a null `tt_division_id` (§5.3). Tags 1..9 each become one `tt_divisions` row per
     * class — the option blocks, up to nine deep in the senior streams.
     */
    private function importGroups(SimpleXMLElement $xml): void
    {
        foreach ($xml->groups->group ?? [] as $g) {
            $ascId = (string) self::attr($g, 'id');
            $name = self::str($g, 'name') ?? 'Group';
            $classAscId = (string) self::attr($g, 'classid');
            $classId = $this->ids->get(IdMap::CLASS_, $classAscId);

            if ($classId === null) {
                $this->report->unmatched(
                    'groups',
                    $ascId,
                    $name,
                    'Its class did not match, so the group was skipped.',
                );

                continue;
            }

            $tag = (int) self::attr($g, 'divisiontag');
            $divisionId = null;

            if ($tag > 0) {
                // firstOrCreate, not updateOrCreate: a division's name may have been
                // edited by an admin, and re-importing must not overwrite that.
                $division = Division::firstOrCreate(
                    ['class_id' => $classId, 'division_tag' => $tag],
                    ['name' => 'Option block '.$tag],
                );

                $divisionId = $division->id;

                if ($division->wasRecentlyCreated) {
                    $this->report->created('divisions');
                }
            }

            $group = Group::updateOrCreate(['asc_id' => $ascId], [
                'class_id' => $classId,
                'tt_division_id' => $divisionId,
                'name' => $name,
                'entire_class' => self::attr($g, 'entireclass') === '1',
                'partner_id' => self::str($g, 'partner_id'),
            ]);

            $this->ids->put(IdMap::GROUP, $ascId, $group->id);

            $group->wasRecentlyCreated
                ? $this->report->created('groups')
                : $this->report->updated('groups');
        }
    }

    private function importLessons(SimpleXMLElement $xml): void
    {
        foreach ($xml->lessons->lesson ?? [] as $l) {
            $ascId = (string) self::attr($l, 'id');
            $subjectAscId = (string) self::attr($l, 'subjectid');
            $subjectId = $this->ids->get(IdMap::SUBJECT, $subjectAscId);

            if ($subjectId === null) {
                $this->report->unmatched(
                    'lessons',
                    $ascId,
                    'subject '.$subjectAscId,
                    'Its subject did not match, so the lesson and its cards were skipped.',
                );

                continue;
            }

            $lesson = Lesson::create([
                'tt_setting_id' => $this->setting->id,
                'subject_id' => $subjectId,
                'periods_per_week' => $this->decimal(self::attr($l, 'periodsperweek')),
                'periods_per_card' => max(1, (int) self::attr($l, 'periodspercard')),
                'tt_daysdef_id' => $this->ids->get(IdMap::DAYSDEF, (string) self::attr($l, 'daysdefid')),
                'tt_weeksdef_id' => $this->ids->get(IdMap::WEEKSDEF, (string) self::attr($l, 'weeksdefid')),
                'tt_termsdef_id' => $this->ids->get(IdMap::TERMSDEF, (string) self::attr($l, 'termsdefid')),
                'seminar_group' => self::seminarGroup(self::attr($l, 'seminargroup')),
                'capacity' => self::capacity(self::attr($l, 'capacity')),
                'asc_id' => $ascId,
            ]);

            $this->ids->put(IdMap::LESSON, $ascId, $lesson->id);

            $lesson->teachers()->sync($this->ids->resolveList(IdMap::TEACHER, self::attr($l, 'teacherids')));
            $lesson->classes()->sync($this->ids->resolveList(IdMap::CLASS_, self::attr($l, 'classids')));
            $lesson->groups()->sync($this->ids->resolveList(IdMap::GROUP, self::attr($l, 'groupids')));

            // classroomids is a ranked preference list, not a set (§5.4) — the index is
            // the ranking and the resolver walks it in order looking for a free room.
            $rooms = [];

            foreach ($this->ids->resolveList(IdMap::ROOM, self::attr($l, 'classroomids')) as $i => $roomId) {
                $rooms[$roomId] = ['sort_order' => $i];
            }

            $lesson->rooms()->sync($rooms);

            $this->warnAboutMissing($ascId, IdMap::TEACHER, self::attr($l, 'teacherids'), 'teacher');
            $this->warnAboutMissing($ascId, IdMap::CLASS_, self::attr($l, 'classids'), 'class');
            $this->warnAboutMissing($ascId, IdMap::GROUP, self::attr($l, 'groupids'), 'group');

            $this->report->created('lessons');
        }
    }

    private function warnAboutMissing(string $lessonAscId, string $type, ?string $ascIds, string $label): void
    {
        $missing = $this->ids->missingFromList($type, $ascIds);

        if ($missing !== []) {
            $this->report->warn(sprintf(
                'Lesson %s references %d unmatched %s(s): %s.',
                $lessonAscId,
                count($missing),
                $label,
                implode(', ', $missing),
            ));
        }
    }

    /**
     * One card is one period. A double is two rows on consecutive periods sharing a
     * days mask, written as-is; nothing here pairs them up, because the pairing is
     * reconstructed from `periods_per_card` when a card is moved (§5.4).
     */
    private function importCards(SimpleXMLElement $xml): void
    {
        foreach ($xml->cards->card ?? [] as $c) {
            $lessonAscId = (string) self::attr($c, 'lessonid');
            $lessonId = $this->ids->get(IdMap::LESSON, $lessonAscId);

            if ($lessonId === null) {
                $this->report->skipped('cards');
                $this->report->warn("Card skipped: lesson {$lessonAscId} was not imported.");

                continue;
            }

            $rooms = $this->ids->resolveList(IdMap::ROOM, self::attr($c, 'classroomids'));

            if (count($rooms) > 1) {
                $this->report->warn(sprintf(
                    'Card on lesson %s names %d rooms; the first was used.',
                    $lessonAscId,
                    count($rooms),
                ));
            }

            Card::create([
                'tt_lesson_id' => $lessonId,
                'period_number' => (int) self::attr($c, 'period'),
                'days' => (new Bitmask((string) self::attr($c, 'days'), $this->cycleLength))->toAscString(),
                'weeks' => self::str($c, 'weeks') ?? '1',
                'terms' => self::str($c, 'terms') ?? '1',
                'tt_room_id' => $rooms[0] ?? null,
                'locked' => self::attr($c, 'locked') === '1',
            ]);

            $this->report->created('cards');
        }
    }

    /**
     * Check every imported option group against the elections, and list the empty ones.
     *
     * This is the plan's "list any group that resolves to zero students so the admin can
     * fix elections". Nothing is written: membership is derived, and a group that comes
     * back empty is a data problem in `student_subjects`, not a fact about the group.
     */
    private function reportDerivedMembership(): void
    {
        $groupIds = array_values($this->ids->all(IdMap::GROUP));

        if ($groupIds === []) {
            return;
        }

        $groups = Group::query()
            ->with(['class', 'lessons.teachers', 'lessons.subject'])
            ->whereIn('id', $groupIds)
            ->get();

        // Groups aSc defines but never timetables. The real export declares 138 groups
        // against 10 lessons, so this is the common case, not an anomaly — one warning
        // per group would bury the handful of entries an admin can actually act on.
        // Counted per class instead, which is the level the answer lives at.
        $lessonless = [];

        foreach ($groups as $group) {
            // A whole-class group's roster is the class itself — there is nothing to
            // derive and nothing that can be missing.
            if ($group->entire_class) {
                continue;
            }

            if ($this->membership->isUnresolvable($group)) {
                $class = $group->class?->name ?? 'unknown class';
                $lessonless[$class] = ($lessonless[$class] ?? 0) + 1;

                continue;
            }

            if ($this->membership->count($group) === 0) {
                $lesson = $group->lessons->first();

                $this->report->emptyGroup(
                    $group->name,
                    $group->class?->name ?? 'unknown class',
                    $lesson?->subject?->name,
                    $lesson?->teachers->first()?->user?->name,
                );
            }
        }

        if ($lessonless !== []) {
            ksort($lessonless);

            $this->report->warn(sprintf(
                '%d group(s) have no lesson in this export, so their subject and teacher '
                .'are unknown: %s. An export with few placed lessons is expected mid-build; '
                .'their membership resolves once the lessons arrive.',
                array_sum($lessonless),
                implode(', ', array_map(
                    static fn (string $class, int $n): string => "{$class} ({$n})",
                    array_keys($lessonless),
                    $lessonless,
                )),
            ));
        }
    }

    // -----------------------------------------------------------------
    // Attribute helpers
    // -----------------------------------------------------------------

    private static function attr(SimpleXMLElement $el, string $name): ?string
    {
        $value = $el->attributes()[$name] ?? null;

        return $value === null ? null : (string) $value;
    }

    /** Trimmed, with aSc's empty string folded to null. */
    private static function str(SimpleXMLElement $el, string $name): ?string
    {
        $value = self::attr($el, $name);

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * `capacity="*"` is aSc's "unlimited" sentinel and must land as null. A plain
     * (int) cast would turn it into 0 and quietly make every room unusable to a
     * capacity check.
     */
    private static function capacity(?string $raw): ?int
    {
        $raw = $raw === null ? '' : trim($raw);

        if ($raw === '' || $raw === '*') {
            return null;
        }

        return is_numeric($raw) ? (int) $raw : null;
    }

    /**
     * The file uses two different "none" conventions for the same field: `"-"` in
     * studentsubjects and `""` in lessons (§5). Both mean no seminar group.
     */
    private static function seminarGroup(?string $raw): ?string
    {
        $raw = $raw === null ? '' : trim($raw);

        return ($raw === '' || $raw === '-') ? null : $raw;
    }

    /** aSc writes "7:30"; the time columns want "07:30:00". */
    private static function time(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $parts = explode(':', trim($raw));

        return sprintf(
            '%02d:%02d:%02d',
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0),
        );
    }

    /** Normalise each mask in a comma-separated list to the cycle width. */
    private static function maskList(?string $raw, int $width): string
    {
        if ($raw === null || trim($raw) === '') {
            return str_repeat('0', $width);
        }

        $masks = [];

        foreach (explode(',', $raw) as $mask) {
            $mask = trim($mask);

            if ($mask !== '') {
                $masks[] = (new Bitmask($mask, $width))->toAscString();
            }
        }

        return $masks === [] ? str_repeat('0', $width) : implode(',', $masks);
    }

    private function decimal(?string $raw): float
    {
        $raw = $raw === null ? '' : trim($raw);

        if ($raw === '') {
            return 0.0;
        }

        // Without the decimalseparatordot flag aSc writes "6,0" rather than "6.0".
        if (! $this->options->has('decimalseparatordot')) {
            $raw = str_replace(',', '.', $raw);
        }

        return (float) $raw;
    }

    private static function truncate(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, $length);
    }

    /**
     * tt_subject_meta and tt_teacher_meta have no Eloquent models — they are pure
     * sidecars on subjects and teachers, each with a unique key on the owning id.
     *
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $values
     */
    private function upsertMeta(string $table, array $key, array $values): void
    {
        $now = now();
        $query = DB::table($table)->where($key);

        if ($query->exists()) {
            $query->update($values + ['updated_at' => $now]);

            return;
        }

        DB::table($table)->insert($key + $values + ['created_at' => $now, 'updated_at' => $now]);
    }
}
