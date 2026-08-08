<?php

namespace Tests\Feature\Timetable;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Tt\Card;
use App\Models\Tt\DaysDef;
use App\Models\Tt\Division;
use App\Models\Tt\Group;
use App\Models\Tt\Lesson;
use App\Models\Tt\Room;
use App\Models\Tt\Setting;
use App\Models\User;
use App\Services\Timetable\Asc\AscOptions;
use App\Services\Timetable\Asc\ImportReport;
use App\Services\Timetable\Asc\XmlImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2 acceptance test for the aSc XML importer.
 *
 * The fixture at tests/Fixtures/Timetable/asc-sample.xml is an anonymised reduction of
 * the school's real Term 2 export (§5.5) — real staff and student names never enter the
 * repository. It is a reduction in scale only: every structural feature the real file
 * exercises is present, and several are here specifically because they are the ones
 * that break a naive importer.
 *
 *   - windows-1252 bytes, with "Zoë" as the proof (a UTF-8 read yields "ZoÃ«")
 *   - capacity="*" on rooms and lessons, which a plain (int) cast turns into 0
 *   - a cross-class option lesson: one lesson over F5A + F5B + F5C
 *   - a 4-way division, plus two same-subject groups split only by teacher
 *   - doubles landing as two consecutive card rows
 *   - "FORM 5A" in the file against "Form 5A" in the database
 *   - unpadded times ("7:30"), a comma-separated "Any day" daysdef, seminargroup "-"
 *   - an aSc subject with no local counterpart, which must be reported not created
 */
class AscImportTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = __DIR__.'/../../Fixtures/Timetable/asc-sample.xml';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSchool();
    }

    // -----------------------------------------------------------------
    // Encoding and file handling
    // -----------------------------------------------------------------

    /**
     * The fixture must actually be windows-1252, or every encoding assertion below is
     * vacuous. This guards the fixture itself: an editor that helpfully re-saves it as
     * UTF-8 would turn the rest of this class into a test of nothing.
     */
    public function test_the_fixture_is_genuinely_windows_1252(): void
    {
        $raw = file_get_contents(self::FIXTURE);

        $this->assertStringContainsString('encoding="windows-1252"', substr($raw, 0, 100));
        $this->assertFalse(
            mb_check_encoding($raw, 'UTF-8'),
            'The fixture parses as UTF-8, so it is no longer a windows-1252 file.',
        );
    }

    public function test_windows_1252_names_survive_the_import_as_utf8(): void
    {
        $report = $this->import();

        // "Zoë" is 0x5A 0x6F 0xEB in cp1252. Read as UTF-8 it becomes "ZoÃ«" — and 0xEB
        // on its own is not valid UTF-8 at all, so a reader that trusted the bytes would
        // not get this far.
        //
        // The student is the assertion that bites. EntityMatcher::student() matches on
        // the full name only, with no fallback, so "Zoë Motsumi" resolves if and only if
        // the transcode was correct and applied exactly once. The teacher below is the
        // weaker check by comparison: "Zoë Kgosi" would still find its way home on the
        // ASCII surname even with the accent mangled.
        $this->assertSame(7, $report->countOf('students', 'matched'));
        $this->assertSame([], $report->unmatchedOfType('students'));

        $motsumi = Student::query()->whereHas('user', fn ($q) => $q->where('name', 'Zoë Motsumi'))->first();
        $this->assertNotNull($motsumi);

        $kgosi = Teacher::query()->whereHas('user', fn ($q) => $q->where('name', 'Zoë Kgosi'))->first();

        $this->assertNotNull($kgosi);
        $this->assertSame(
            'T3',
            DB::table('tt_teacher_meta')->where('teacher_id', $kgosi->id)->value('short_name'),
        );
        $this->assertSame([], $report->unmatchedOfType('teachers'));
    }

    public function test_a_missing_file_fails_loudly(): void
    {
        $this->expectExceptionMessageMatches('/not readable/');

        (new XmlImporter)->import(__DIR__.'/does-not-exist.xml');
    }

    // -----------------------------------------------------------------
    // Counts and structure
    // -----------------------------------------------------------------

    public function test_the_fixture_imports_with_the_expected_counts(): void
    {
        $report = $this->import();
        $setting = Setting::findOrFail($report->settingId());

        $this->assertSame(8, $setting->periods()->count());
        $this->assertSame(1, $setting->breaks()->count());
        $this->assertSame(2, $setting->daysdefs()->count());
        $this->assertSame(1, $setting->weeksdefs()->count());
        $this->assertSame(1, $setting->termsdefs()->count());
        $this->assertSame(3, $setting->rooms()->count());

        // Five lessons, not six: the Astronomy lesson names a subject the school does
        // not have, so it is skipped rather than importing against an invented subject.
        $this->assertSame(5, $setting->lessons()->count());

        // Six cards, not seven, for the same reason — the seventh belongs to the
        // skipped lesson.
        $this->assertSame(6, Card::query()->count());

        $this->assertSame(10, Group::query()->count());

        // Four divisions across the three classes: Form 5A splits twice (the 4-way
        // option block and the Physics block), 5B and 5C once each.
        $this->assertSame(4, Division::query()->count());
    }

    /**
     * A fresh import is a draft, never a live timetable.
     *
     * The school re-exports as it works (the real file is "Rev 5"), so an import must
     * not be able to disturb what is currently on screen for teachers and parents.
     */
    public function test_an_import_lands_as_an_inactive_draft_revision(): void
    {
        $report = $this->import();
        $setting = Setting::findOrFail($report->settingId());

        $this->assertFalse($setting->is_active);
        $this->assertFalse($setting->is_published);
        $this->assertSame(6, (int) $setting->cycle_length);
        $this->assertSame(1, (int) $setting->revision);
    }

    public function test_the_cycle_length_is_read_from_the_files_own_masks(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        // Six, because the fixture's masks are six wide — not because 6 is hardcoded.
        $this->assertSame(6, (int) $setting->cycle_length);
    }

    public function test_periods_keep_their_times_and_the_break_sits_after_period_four(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        $first = $setting->periods()->first();

        // aSc writes "7:30"; the time column wants "07:30:00".
        $this->assertSame(1, (int) $first->period_number);
        $this->assertSame('07:30:00', $this->timeOf($first->start_time));
        $this->assertSame('08:10:00', $this->timeOf($first->end_time));

        // aSc's break="5" means "before period 5". after_period is the other way round,
        // and the clock settles it: period 4 ends 10:10, the break runs 10:10-10:30.
        $break = $setting->breaks()->first();

        $this->assertSame(4, (int) $break->after_period);
        $this->assertSame('10:10:00', $this->timeOf($break->start_time));
        $this->assertSame('10:30:00', $this->timeOf($break->end_time));
    }

    public function test_an_any_day_daysdef_keeps_all_six_alternatives(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        $anyDay = $setting->daysdefs()->where('name', 'Any day')->firstOrFail();
        $everyDay = $setting->daysdefs()->where('name', 'Every day')->firstOrFail();

        // Six separate one-day alternatives, read back through masks() rather than by
        // hand-splitting the column.
        $this->assertSame(
            ['100000', '010000', '001000', '000100', '000010', '000001'],
            array_map(fn ($m) => $m->toAscString(), $anyDay->masks()),
        );

        // "Every day" is one mask with every bit set — a different thing entirely, and
        // the pair is here so a parser that confuses them fails.
        $this->assertCount(1, $everyDay->masks());
        $this->assertSame('111111', $everyDay->masks()[0]->toAscString());
        $this->assertSame(6, $everyDay->masks()[0]->width());
    }

    /**
     * capacity="*" is aSc's "unspecified" sentinel and must land as null.
     *
     * A plain (int) cast yields 0, which reads as "this room seats nobody" and would
     * make every capacity check in Phase 5 reject every placement.
     */
    public function test_a_star_capacity_becomes_null_not_zero(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        $starred = $setting->rooms()->where('name', 'Biology Lab R1')->firstOrFail();
        $numbered = $setting->rooms()->where('name', 'Room 3')->firstOrFail();

        $this->assertNull($starred->capacity);
        $this->assertSame(30, (int) $numbered->capacity);

        // Same sentinel on lessons.
        $physics = $this->lessonFor($setting, 'Physics');
        $chemistry = $this->lessonFor($setting, 'Chemistry');

        $this->assertNull($physics->capacity);
        $this->assertSame(24, (int) $chemistry->capacity);
    }

    // -----------------------------------------------------------------
    // The shapes the legacy schema could not represent
    // -----------------------------------------------------------------

    /**
     * One lesson serving three streams at once — the case §5.4 confirmed in the real
     * export and the one the legacy timetable_entries table cannot express at all.
     */
    public function test_a_cross_class_option_lesson_keeps_all_three_classes_and_groups(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());
        $physics = $this->lessonFor($setting, 'Physics');

        $this->assertSame(
            ['Form 5A', 'Form 5B', 'Form 5C'],
            $physics->classes->pluck('name')->sort()->values()->all(),
        );
        $this->assertCount(3, $physics->groups);
        $this->assertCount(1, $physics->teachers);
        $this->assertSame('Zoë Kgosi', $physics->teachers->first()->user->name);

        // classroomids is a ranked preference list, not a set: sort_order preserves the
        // order the file gave, because the resolver walks it looking for a free room.
        $this->assertSame(
            ['Biology Lab R1', 'Room 2', 'Room 3'],
            $physics->rooms->pluck('name')->all(),
        );
        $this->assertSame([0, 1, 2], $physics->rooms->map(fn (Room $r) => (int) $r->pivot->sort_order)->all());
    }

    public function test_a_four_way_division_becomes_one_division_with_four_groups(): void
    {
        $this->import();

        $formFiveA = ClassModel::where('name', 'Form 5A')->firstOrFail();

        // Two divisions on this class: tag 1 (the 4-way block) and tag 2 (the
        // cross-class Physics block). divisiontag="0" is the entire-class sentinel and
        // must NOT have produced a third.
        $this->assertSame([1, 2], Division::where('class_id', $formFiveA->id)
            ->orderBy('division_tag')->pluck('division_tag')
            ->map(fn ($t) => (int) $t)->all());

        $block = Division::where('class_id', $formFiveA->id)->where('division_tag', 1)->firstOrFail();

        $this->assertSame(4, $block->groups()->count());
        $this->assertSame(
            ['BIO A', 'CHE A', 'SET_Chisenga', 'SET_Simukonda'],
            $block->groups()->pluck('name')->sort()->values()->all(),
        );

        foreach ($block->groups as $group) {
            $this->assertFalse($group->entire_class);
            $this->assertSame((int) $formFiveA->id, (int) $group->class_id);
        }
    }

    /**
     * Two groups, same subject, different teacher — the MaE_Sim / MaE_Chisenga shape
     * from §5.6. They must stay distinct, because that pair is exactly what group
     * membership derivation keys on.
     */
    public function test_same_subject_groups_split_by_teacher_stay_distinct(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        $setswana = Lesson::query()
            ->where('tt_setting_id', $setting->id)
            ->whereHas('subject', fn ($q) => $q->where('name', 'Setswana'))
            ->with(['teachers.user', 'groups'])
            ->get();

        $this->assertCount(2, $setswana);

        $byTeacher = $setswana->mapWithKeys(fn (Lesson $l) => [
            $l->teachers->first()->user->name => $l->groups->first()->name,
        ]);

        $this->assertSame('SET_Simukonda', $byTeacher['Kabelo Simukonda']);
        $this->assertSame('SET_Chisenga', $byTeacher['Naledi Chisenga']);
    }

    public function test_the_entire_class_group_carries_no_division(): void
    {
        $this->import();

        $entireClass = Group::where('entire_class', true)->get();

        $this->assertCount(3, $entireClass);

        foreach ($entireClass as $group) {
            $this->assertNull(
                $group->tt_division_id,
                'divisiontag="0" is aSc\'s whole-class sentinel, not a division.',
            );
        }
    }

    /**
     * A double is two card rows on consecutive periods sharing one days mask and one
     * room — there is no card-group id in the file, so that shape is the only record
     * that the two belong together.
     */
    public function test_a_double_lands_as_two_consecutive_cards(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());
        $physics = $this->lessonFor($setting, 'Physics');

        $this->assertSame(2, (int) $physics->periods_per_card);
        $this->assertEquals(6.0, (float) $physics->periods_per_week);

        // 6.0 periods a week at 2 a card is three doubles; the fixture places two.
        $this->assertSame(3, $physics->cardsRequired());

        $cards = $physics->cards()->orderBy('period_number')->get();

        $this->assertCount(4, $cards);

        $byDay = $cards->groupBy('days');

        $this->assertSame(
            [3, 4],
            $byDay['001000']->pluck('period_number')->map(fn ($n) => (int) $n)->all(),
        );
        $this->assertSame(
            [7, 8],
            $byDay['000001']->pluck('period_number')->map(fn ($n) => (int) $n)->all(),
        );

        // Room is a per-card property, not per-lesson: the same lesson uses different
        // rooms on different days, exactly as the real export does.
        $this->assertSame(1, $byDay['001000']->pluck('tt_room_id')->unique()->count());
        $this->assertNotSame(
            (int) $byDay['001000']->first()->tt_room_id,
            (int) $byDay['000001']->first()->tt_room_id,
        );
    }

    public function test_a_card_mask_is_six_wide_over_the_cycle(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());
        $card = $this->lessonFor($setting, 'Physics')->cards()->orderBy('period_number')->firstOrFail();

        $mask = $card->daysMask();

        $this->assertSame(6, $mask->width());
        $this->assertSame('001000', $mask->toAscString());
        $this->assertSame([3], $mask->positions());
    }

    // -----------------------------------------------------------------
    // Matching and the report
    // -----------------------------------------------------------------

    public function test_classes_match_across_the_form_casing_difference(): void
    {
        $report = $this->import();

        // "FORM 5A" in the file, "Form 5A" in the database (§5.6).
        $this->assertSame(3, $report->countOf('classes', 'matched'));
        $this->assertSame([], $report->unmatchedOfType('classes'));
        $this->assertSame(3, ClassModel::query()->count(), 'No class may be created by an import.');
    }

    public function test_subjects_match_by_code_then_by_name(): void
    {
        $report = $this->import();

        // Setswana matches on code (aSc short "SET" against subjects.code "SET");
        // Biology has no code overlap ("Bi" vs "BIO") and matches on name instead.
        $this->assertSame(4, $report->countOf('subjects', 'matched'));

        $biology = Subject::where('name', 'Biology')->firstOrFail();

        $this->assertSame(
            'Bi',
            DB::table('tt_subject_meta')->where('subject_id', $biology->id)->value('short_name'),
        );
    }

    /**
     * The whole point of the report. An aSc entity with no local row is listed for a
     * human, never invented — a duplicate "Astronomy" would split a subject's marks in
     * two with no error anywhere.
     */
    public function test_an_unmatched_subject_is_reported_and_never_created(): void
    {
        $report = $this->import();

        $unmatched = $report->unmatchedOfType('subjects');

        $this->assertCount(1, $unmatched);
        $this->assertSame('Astronomy', $unmatched[0]['name']);
        $this->assertSame('5555555555555555', $unmatched[0]['asc_id']);

        $this->assertSame(4, Subject::query()->count());
        $this->assertNull(Subject::where('name', 'Astronomy')->first());

        // And its lesson went with it, rather than importing subject-less.
        $this->assertCount(1, $report->unmatchedOfType('lessons'));
        $this->assertFalse($report->isClean());
    }

    public function test_teachers_match_on_an_unambiguous_surname(): void
    {
        $report = $this->import();

        // The file writes "K Simukonda"; the database holds "Kabelo Simukonda".
        $this->assertSame(3, $report->countOf('teachers', 'matched'));

        $setting = Setting::findOrFail($report->settingId());
        $biology = $this->lessonFor($setting, 'Biology')->load('teachers.user');

        $this->assertSame('Kabelo Simukonda', $biology->teachers->first()->user->name);
    }

    /**
     * Group membership is derived from elections, not from the file (§5.3), so a group
     * that resolves to nobody means the elections are wrong. The admin needs that list.
     */
    public function test_a_group_with_no_electors_is_reported_rather_than_silently_empty(): void
    {
        $report = $this->import();

        $empty = collect($report->emptyGroups());

        // SET_Chisenga: Setswana taught by Chisenga in Form 5A, which nobody elected.
        // SET_Simukonda has one elector and must NOT appear.
        $this->assertSame(['SET_Chisenga'], $empty->pluck('group')->all());
        $this->assertSame('Form 5A', $empty->first()['class']);
        $this->assertSame('Setswana', $empty->first()['subject']);
        $this->assertSame('Naledi Chisenga', $empty->first()['teacher']);
    }

    /**
     * The importer never writes tt_group_student. Membership is derived at read time,
     * and that pivot is an override list only — writing a snapshot into it would freeze
     * today's elections and silently diverge the moment a student changed option.
     */
    public function test_the_import_writes_no_group_membership_overrides(): void
    {
        $this->import();

        $this->assertSame(0, DB::table('tt_group_student')->count());
    }

    // -----------------------------------------------------------------
    // Dry run and re-import
    // -----------------------------------------------------------------

    public function test_a_dry_run_reports_in_full_and_writes_nothing(): void
    {
        $report = (new XmlImporter)->import(self::FIXTURE, ['dry_run' => true]);

        // The report is as complete as a real run's — the import genuinely happened and
        // was then rolled back, which is the only way to measure it honestly.
        $this->assertTrue($report->dryRun);
        $this->assertSame(5, $report->countOf('lessons', 'created'));
        $this->assertSame(3, $report->countOf('classes', 'matched'));
        $this->assertCount(1, $report->unmatchedOfType('subjects'));
        $this->assertCount(1, $report->emptyGroups());

        // And nothing survived it.
        $this->assertNull($report->settingId());
        $this->assertSame(0, Setting::query()->count());
        $this->assertSame(0, Lesson::query()->count());
        $this->assertSame(0, Card::query()->count());
        $this->assertSame(0, Group::query()->count());
        $this->assertSame(0, Division::query()->count());
        $this->assertSame(0, DB::table('tt_subject_meta')->count());
    }

    /**
     * Re-importing produces a second draft revision rather than mutating the first.
     *
     * The school cuts a file per term and re-exports as it works, so imports are
     * snapshots. Setting-scoped rows duplicate by design; the tables that hang off the
     * academic side instead — groups, divisions, the meta sidecars — must not.
     */
    public function test_re_importing_adds_a_revision_without_duplicating_groups(): void
    {
        $first = $this->import();
        $second = $this->import();

        $this->assertNotSame($first->settingId(), $second->settingId());
        $this->assertSame(2, Setting::query()->count());
        $this->assertSame(1, (int) Setting::findOrFail($first->settingId())->revision);
        $this->assertSame(2, (int) Setting::findOrFail($second->settingId())->revision);

        // Setting-scoped: one copy per revision.
        $this->assertSame(10, Lesson::query()->count());
        $this->assertSame(6, Room::query()->count());

        // Class-scoped: keyed on asc_id and the (class, tag) pair, so still one each.
        $this->assertSame(10, Group::query()->count());
        $this->assertSame(4, Division::query()->count());

        // Subject- and teacher-scoped sidecars: one row each, updated not duplicated.
        $this->assertSame(4, DB::table('tt_subject_meta')->count());
        $this->assertSame(3, DB::table('tt_teacher_meta')->count());

        // The second revision's lessons point at the second revision's groups.
        $secondPhysics = $this->lessonFor(Setting::findOrFail($second->settingId()), 'Physics');
        $this->assertCount(3, $secondPhysics->groups);
    }

    // -----------------------------------------------------------------
    // Round-trip fidelity
    // -----------------------------------------------------------------

    /**
     * Phase 9 has to hand this data back to aSc, and aSc treats the root options string
     * and each section's columns list as part of the file's identity. Regenerating them
     * from the schema would reorder or drop fields, so they are kept verbatim.
     */
    public function test_the_root_options_and_section_columns_are_preserved_verbatim(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        $options = AscOptions::fromJson($setting->asc_options);

        $this->assertSame('2026.10.1', $options->version());
        $this->assertSame('database', $options->attribute('importtype'));
        $this->assertTrue($options->has('groupstype1'));
        $this->assertTrue($options->has('decimalseparatordot'));
        $this->assertTrue($options->has('export:idprefix'));
        $this->assertFalse($options->has('nosuchflag'));

        $this->assertSame(
            'period,name,short,starttime,endtime',
            $options->columnsFor('periods'),
        );
        $this->assertSame(
            'lessonid,period,days,weeks,terms,classroomids',
            $options->columnsFor('cards'),
        );

        // Even the two empty sections keep their columns, because aSc expects them back.
        $this->assertSame('id,name,partner_id', $options->columnsFor('buildings'));
    }

    public function test_asc_ids_are_stored_so_the_export_can_re_emit_them(): void
    {
        $setting = Setting::findOrFail($this->import()->settingId());

        $this->assertSame('7000000000000001', $this->lessonFor($setting, 'Physics')->asc_id);
        $this->assertSame(
            '1000000000000002',
            Group::where('name', 'BIO A')->firstOrFail()->asc_id,
        );
        $this->assertSame(
            'DDDDDDDDDDDDDDD2',
            DaysDef::where('name', 'Any day')->firstOrFail()->asc_id,
        );
    }

    // -----------------------------------------------------------------
    // Fixture
    // -----------------------------------------------------------------

    private function import(): ImportReport
    {
        return (new XmlImporter)->import(self::FIXTURE, ['term_label' => 'Term 2']);
    }

    private function lessonFor(Setting $setting, string $subject): Lesson
    {
        return Lesson::query()
            ->where('tt_setting_id', $setting->id)
            ->whereHas('subject', fn ($q) => $q->where('name', $subject))
            ->with(['classes', 'groups', 'teachers.user', 'rooms', 'cards'])
            ->firstOrFail();
    }

    /** sqlite hands back "07:30:00"; MariaDB may hand back a Carbon-parsed value. */
    private function timeOf(mixed $value): string
    {
        return $value instanceof \DateTimeInterface
            ? $value->format('H:i:s')
            : substr((string) $value, -8);
    }

    /**
     * The school as the database holds it — deliberately NOT as the XML holds it.
     *
     * Class names are "Form 5A" against the file's "FORM 5A", teachers are full names
     * against the file's initial-plus-surname, subject codes are three letters against
     * aSc's two, and Astronomy does not exist at all. Every one of those gaps is a
     * matching path the importer has to cross or report.
     */
    private function seedSchool(): void
    {
        $year = AcademicYear::create([
            'year_name' => '2026',
            'active' => true,
            'status' => AcademicYear::STATUS_OPEN,
        ]);

        $classes = collect(['Form 5A', 'Form 5B', 'Form 5C'])->mapWithKeys(
            fn (string $name) => [$name => ClassModel::create([
                'name' => $name,
                'level' => 5,
                'academic_year_id' => $year->id,
            ])],
        );

        $subjects = collect([
            ['name' => 'Biology', 'code' => 'BIO'],
            ['name' => 'Chemistry', 'code' => 'CHE'],
            ['name' => 'Physics', 'code' => 'PHY'],
            ['name' => 'Setswana', 'code' => 'SET'],
        ])->mapWithKeys(fn (array $a) => [
            $a['name'] => Subject::create($a + ['is_active' => true]),
        ]);

        $teachers = collect(['Kabelo Simukonda', 'Naledi Chisenga', 'Zoë Kgosi'])
            ->mapWithKeys(function (string $name, int $i) {
                $user = User::factory()->create([
                    'name' => $name,
                    'email' => 'asc.teacher.'.$i.'@example.test',
                    'role' => 'teacher',
                    'status' => 'active',
                ]);

                return [$name => Teacher::create(['user_id' => $user->id])];
            });

        $students = collect([
            ['Tebogo Moeng', 'Form 5A', 'male'],
            ['Naledi Phiri', 'Form 5A', 'female'],
            ['Zoë Motsumi', 'Form 5A', 'female'],
            ['Kabelo Ntsima', 'Form 5A', 'male'],
            ['Lesego Bane', 'Form 5B', 'female'],
            ['Mpho Radise', 'Form 5B', 'male'],
            ['Onalenna Seru', 'Form 5C', 'female'],
        ])->mapWithKeys(function (array $row, int $i) use ($classes) {
            [$name, $className, $gender] = $row;

            $user = User::factory()->create([
                'name' => $name,
                'email' => 'asc.student.'.$i.'@example.test',
                'role' => 'student',
                'status' => 'active',
            ]);

            return [$name => Student::create([
                'user_id' => $user->id,
                'admission_no' => 'ASC-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'gender' => $gender,
                'date_of_birth' => '2009-01-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'current_class_id' => $classes[$className]->id,
            ])];
        });

        // The elections that group membership derives from. Note what is absent:
        // nobody takes Setswana with Chisenga, which is what makes SET_Chisenga the
        // empty group the report has to surface.
        $elections = [
            ['Tebogo Moeng', 'Biology', 'Kabelo Simukonda', 'Form 5A'],
            ['Naledi Phiri', 'Biology', 'Kabelo Simukonda', 'Form 5A'],
            ['Zoë Motsumi', 'Chemistry', 'Naledi Chisenga', 'Form 5A'],
            ['Kabelo Ntsima', 'Setswana', 'Kabelo Simukonda', 'Form 5A'],
            ['Tebogo Moeng', 'Physics', 'Zoë Kgosi', 'Form 5A'],
            ['Lesego Bane', 'Physics', 'Zoë Kgosi', 'Form 5B'],
            ['Onalenna Seru', 'Physics', 'Zoë Kgosi', 'Form 5C'],
        ];

        foreach ($elections as [$student, $subject, $teacher, $className]) {
            StudentSubject::create([
                'student_id' => $students[$student]->id,
                'subject_id' => $subjects[$subject]->id,
                'teacher_id' => $teachers[$teacher]->id,
                'class_id' => $classes[$className]->id,
                'academic_year_id' => $year->id,
                'is_elective' => false,
            ]);
        }
    }
}
