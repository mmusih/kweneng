# Timetable rebuild: mirroring aSc TimeTables

Working document for the `codex/timetable-features` branch. It records what exists
today, where it falls short of the aSc TimeTables model, and the phased plan to
close the gap. Written so any session can pick up mid-flight: each phase lists the
files it touches, the acceptance checks, and a "done when" line.

**Status: planning only. No code changed yet.**

Last updated: 2026-08-07

---

## 1. What exists today

Migrations `database/migrations/2026_07_28_000002_create_timetable_tables.php` and
`2026_07_29_000001_create_timetable_cycle_anchors_table.php` create eight tables:

| Table | Shape |
| --- | --- |
| `timetable_templates` | per academic year; `cycle_type` weekly\|rotating, `cycle_length`, `cycle_start_date`, `is_active`, `is_published` |
| `timetable_days` | per template; `day_number`, `name`, `weekday` (null for rotating) |
| `timetable_periods` | **per day**; `sequence`, `name`, `start_time`, `end_time`, `type` (lesson/break/lunch/assembly/other) |
| `timetable_rooms` | flat list; `capacity`, `type` |
| `timetable_groups` | per year + **per subject**; ad-hoc named student set |
| `timetable_group_class`, `timetable_group_student` | group membership pivots |
| `timetable_entries` | one placed lesson: day + start/end period + (class XOR group) + subject + teacher + optional room |
| `timetable_cycle_anchors` | date → cycle day, for resuming a rotating cycle after a break |

Code:

- `app/Services/TimetableService.php:82` — `validateEntry()`, all conflict rules.
- `app/Services/TimetableService.php:193` — `payload()`, the read model consumed by
  web + API + both Flutter apps.
- `app/Http/Controllers/Admin/TimetableController.php` — 9 form endpoints, one
  create-form per concept, no update endpoints at all.
- `app/Http/Controllers/TimetableController.php`, `.../Api/TimetableController.php` —
  read-only teacher/student/parent views.
- `resources/views/admin/timetable/index.blade.php` — 514 lines of stacked forms plus
  a flat "Scheduled lessons" table.
- `resources/views/timetable/viewer.blade.php` — day-tabbed card list.
- `tests/Feature/TimetableTest.php` — 5 tests (template creation, rotating cycle,
  teacher clash, split groups, cross-role viewing).

What genuinely works and must survive the rebuild: the rotating-cycle anchor maths
(`app/Models/TimetableTemplate.php:53`), the per-role read endpoints, the published/
active template gate, and the JSON payload contract the shipped Flutter apps read.

---

## 2. Where it falls short of aSc

Ordered by how much each one blocks the others.

**2.1 No lesson/card split.** This is the root problem. aSc separates a *lesson*
(subject X, to class/group Y, by teacher Z, N periods per week) from a *card* (one
placement of that lesson at a period, on given days). `timetable_entries` fuses both,
so the system cannot express "Maths needs 5 periods/week, 3 are placed", cannot move
a placement without re-entering the demand, and has nothing for a generator to solve
against. Every later gap follows from this.

**2.2 Periods are per-day, not global.** aSc has one `periods` list for the school and
a separate `daysdefs`. Here `timetable_periods.timetable_day_id` means "Period 1" is
duplicated once per day, and a card cannot carry a `days` bitmask because its period
id already implies a single day. Fixing 2.1 without fixing this is not possible.

**2.3 Single-valued lesson participants.** `timetable_entries` has one `teacher_id`,
one `class_id`, one `timetable_group_id`, one `timetable_room_id`. aSc lessons take
lists. No co-teaching, no joint lesson across two classes, no lesson taught to two
option groups at once, no "any of these three labs" room set.

**2.4 Wrong group model.** aSc: a class has *divisions* (halves, thirds), a division
has *groups*, and the whole class is an implicit group. Here `timetable_groups` are
subject-scoped hand-picked student lists with `unique(academic_year_id, name)` and a
rule that the group's subject must equal the lesson's subject
(`app/Services/TimetableService.php:114`). Expressing "Group 1 of halves does Maths
while Group 2 does Science, same period" needs division-aware groups.

**2.5 No weeks or terms.** aSc carries `weeksdefs` (A/B weeks) and `termsdefs`
(term-specific timetables) as bitmasks on every card. Absent here. The rotating-cycle
anchor mechanism is a different, non-aSc idea; keep it, but as a layer on top.

**2.6 No constraints.** aSc's value is its constraint set: teacher/class/room
availability grids, max lessons per day, max gaps, no-gaps, double-period
requirements, subject-max-per-day, lessons-in-a-row, relation constraints, each with a
weight. Here there are exactly three hard rules baked into `validateEntry()` (teacher
clash, room clash, student clash) plus a teacher-must-be-assigned check.

**2.7 No generator.** Placement is one HTML form POST at a time. aSc's core is
automatic generation with backtracking plus a "test/relax" report.

**2.8 No grid editor.** aSc's interaction is drag-drop on a class/teacher/room grid
with live conflict shading and possible-placement highlighting. The admin page cannot
even edit an entry — only create and delete.

**2.9 No verification report.** Conflicts are rejected at insert time with a single
message. There is no whole-timetable audit: unplaced lessons, over/under-allocated
teachers, rooms double-booked across weeks, classes with gaps.

**2.10 No import/export or print.** No aSc XML round-trip, no printable class/teacher/
room timetables, no substitutions model.

**2.11 Two small real bugs to fix in passing.**
`payload()` returns `days` as a Collection while `emptyPayload()` returns `[]`, so the
`$schedule['days'] === []` check at `resources/views/timetable/viewer.blade.php:35`
never fires; a template with zero days then hits
`$schedule['days'][0]['day_number']` in the inline script and throws. Also
`storePeriod()` checks time overlap but nothing keeps `sequence` ordering consistent
with `start_time`.

---

## 3. Target model

Mirror aSc's entity names so the concepts (and later the XML round-trip) line up.
New tables, all prefixed `tt_` to sit clearly apart from the legacy `timetable_*`
tables during the migration window.

```
tt_settings          one row per template: cycle_length, week & term model, asc_options
tt_periods           GLOBAL period list: period_number, name, short, start_time, end_time
tt_breaks            break_number (= "before period N"), name, short, start_time, end_time
tt_daysdefs          named day sets: name, short, masks (JSON list of bitmask strings)
tt_weeksdefs         named week sets: "All weeks" = 1
tt_termsdefs         named term sets: "Whole year" = 1
tt_divisions         class_id, division_tag, name — an option block
tt_groups            class_id, division_id (null = whole class), name, entire_class,
                     asc_id, partner_id
tt_group_student     explicit membership; usually derived from student_subjects
tt_subjects_meta     per-subject: short, colour, asc_id, partner_id
tt_teacher_meta      per-teacher: short, colour, max_lessons_per_day, asc_id
tt_rooms             name, short, capacity (nullable = "*"), building, asc_id
tt_lessons           the DEMAND: template, subject, periods_per_week DECIMAL(4,1),
                     periods_per_card, daysdef_id, weeksdef_id, termsdef_id,
                     seminar_group, capacity (nullable), asc_id
tt_lesson_teacher    lesson ⇄ teachers (co-teaching)
tt_lesson_class      lesson ⇄ classes (cross-class option blocks)
tt_lesson_group      lesson ⇄ tt_groups (one group per participating class)
tt_lesson_room       lesson ⇄ candidate rooms, ordered by preference
tt_cards             the PLACEMENT: lesson_id, period_number, days bitmask,
                     weeks bitmask, terms bitmask, room_id, locked
tt_constraints       type, weight (0-100), scope, scope_id, params JSON
tt_generation_runs   audit: template, started_at, finished_at, status, stats, seed
```

Key modelling decisions, and why:

- **Global periods, bitmask days.** `tt_cards.days` is a string bitmask exactly as aSc
  writes it (`010000` = Day 2 of a 6-day cycle). Mask width equals the template's
  `cycle_length`, so the `Bitmask` object takes an explicit width — do not assume 5
  or 7. A lesson's `daysdef` constrains where the generator may place; the card's
  `days` records where it landed.
- **One card = one period.** A `periods_per_card = 2` double is two card rows on
  consecutive periods sharing a `days` mask (§5.4). Placement writes, moves and
  deletes them as a unit; the schema stores them separately, matching aSc.
- **Unplaced work** = `periods_per_week` − count of that lesson's cards. This is the
  number the verification report leads with.
- **Divisions are option blocks, not equal splits.** A division is a named set of N
  parallel groups where N varies (2–4 in this school's data), the group names are
  subject choices, and the same subject may appear twice under different teachers.
  No `parts_count` column.
- **Group membership derives from `student_subjects`.** aSc leaves `studentids`
  empty on division groups (§5.3); the school's real membership signal is each
  student's subject election, which this project already models. `tt_group_student`
  exists for manual override only.
- **Bitmasks stored as strings.** Keeps the aSc XML round-trip lossless and readable
  in the DB. Helpers live in a single `App\Support\Timetable\Bitmask` value object.
- **Constraints as rows, not columns.** `type` + `params` JSON + `weight` means adding
  a constraint kind is a seeder change, not a migration. Hard constraints are
  `weight = 100`.
- **`asc_id` and `partner_id` on every importable entity.** Preserves aSc's opaque
  string ids so re-import updates rather than duplicates, and keeps export lossless.
- **Keep `timetable_cycle_anchors`.** The rotating-cycle anchor logic is ours, not
  aSc's, and it works. It maps a calendar date to a `day_number`; the new resolver
  turns that `day_number` into a bit position, so anchors keep working unchanged.

Legacy mapping, applied by the data migration: one `timetable_entries` row becomes one
`tt_lessons` (with `periods_per_week` summed across its rows for the same
subject+teacher+class trio) plus one `tt_cards` row per original entry, with `days` set
from the entry's `timetable_day.day_number` and `weeks`/`terms` set to "always".

---

## 4. Evidence from the school's live aSc file

Source: `docs/asc.png`, a screenshot of aSc TimeTables 2026 with
`2026 Term 2 Rev 5.roz` open in the class-grid view. This is the real target to
match, and it settles several things the plan had been guessing at.

**Cycle and periods.** Columns run **Day 1 … Day 6**, each split into **8 periods**.
Not Monday–Friday — a genuine 6-day rotating cycle. The existing
`cycle_type = 'rotating'`, `cycle_length = 6` work and the `timetable_cycle_anchors`
mechanism are aimed correctly and must survive.

**Classes.** Visible rows: F1A, F1B, F2A, F2B, F3A, F4A, F4B, F5A, F5B, F5C — ten
streams across Forms 1–5 (the grid may scroll; treat ten as a floor, not a cap).

**Option blocks are the dominant structure, not an edge case.** Junior rows (F1–F2)
are mostly whole-class single subjects, with the occasional two-way language split
(`Fr` over `Set`). But F3A, F4A, F4B, F5A, F5B and F5C — six of the ten streams —
show cells stacked **2, 3 and 4 subjects deep**: `Ch`/`BS`; `Ge`/`ECO`/`LIB`;
`Ph`/`Ge`/`ECO`; `EL`/`Ph`/`Ag`/`Ch`. Each stack is one period in which the class
splits into parallel groups, each with its own subject, teacher and room.

This reframes gap 2.4 from a refinement into the single most important modelling
change after the lesson/card split. A schema that cannot express "four groups of one
class, same period, four different subjects" cannot represent most of this school's
senior timetable. The current `timetable_groups` — hand-picked, subject-scoped,
`unique(academic_year_id, name)` — cannot.

**Doubles are the default.** Reading F1A across Day 1: periods 1–2 `Fr`, 3–4 `En`,
5–6 `Bi`, 7–8 `Ma`. Nearly every lesson is a double. So `periods_per_card = 2` is the
common case and the generator must treat doubles as normal, not as a special
constraint bolted on afterwards.

**Subjects carry colour as primary information.** Roughly two dozen short codes —
`Fr Set En Bi Ma CS Ge T&T LIB Ag Acc BS Ch Ph EL ECO Esl Efl Add Maths MaE MaC` —
each with a stable distinct colour. At this density the grid is unreadable without
it. Confirms `tt_subjects_meta.colour` and the need for a short code separate from
the display name.

**Terms are real; A/B weeks are not in evidence.** The file is named "Term 2", the
toolbar carries a term selector reading "Whole", and the file is at revision 5 —
they re-cut the timetable per term and iterate within it.

**aSc's own information architecture**, from the toolbar, worth mirroring in the
admin nav: Subjects · Classes · Classrooms · Teachers · Students/Seminars ·
Relations · **Test** · **Generate** · **Verification** · Print / Print preview.
Test, Generate and Verification sit at the same level as the data screens — they are
primary actions, not buried tools. Print has two top-level buttons.

**Consequences for the plan below:**

1. Divisions and multi-group lessons move into Phase 1's schema as a first-class
   concern; Phase 4 must ship option-block editing, not defer it.
2. Test fixtures throughout should mirror the real shape — 6-day cycle, 8 periods,
   ~10 streams, doubles as default, option blocks up to 4 deep — rather than the
   toy 5-day fixture in `tests/Feature/TimetableTest.php`.
3. `tt_termsdefs` is required. A/B week support stays in schema (it is nearly free
   once bitmasks exist) but gets no UI.
4. Verification and print are not late polish; they are how this school checks its
   work. Phase 8 stays where it is for dependency reasons, but nothing earlier
   should make them harder to add.

---

## 5. Evidence from the school's aSc XML export

Source: `docs/term 2 sample.xml` — a real aSc 2026.10.1 export,
`importtype="database"`, 232 KB. This is the authoritative schema reference; the
sections below supersede any guess in §3 where they conflict.

### 5.1 Element counts

| Element | Count | Notes |
| --- | --- | --- |
| `period` | 8 | 07:30–13:10, 40-minute slots |
| `break` | 1 | "Breaktime" `break="5"`, 10:10–10:30 — sits *between* periods 4 and 5 |
| `daysdef` | 8 | Day 1–6 plus "Any day" and "Every day" |
| `weeksdef` | 1 | "All weeks" `weeks="1"` — no A/B weeks |
| `termsdef` | 1 | "Whole year" `terms="1"` |
| `subject` | 31 | |
| `teacher` | 20 | each with `short` (T3, T4… plus "LiB") and a hex `color` |
| `classroom` | 15 | `capacity="*"` throughout — capacity unused |
| `class` | 10 | F1A…F5C, each with a `teacherid` (form teacher) and home `classroomids` |
| `group` | 138 | the important one — see 5.3 |
| `student` | 128 | |
| `studentsubject` | 1223 | per-student subject election |
| `lesson` | 10 | **partial export** |
| `card` | 10 | **partial export** |

The 10 lessons / 10 cards are a sample slice, not the full timetable — the
screenshot shows several hundred placements. Treat lessons/cards here as a *format*
reference and the screenshot as the *scale* reference.

### 5.2 Corrections to §3

- **Breaks are their own element**, not a period type. `<breaks>` carries
  `break="5"`, meaning "before period 5". The current design folds breaks into
  `timetable_periods.type`, which cannot round-trip. Add `tt_breaks` with an
  `after_period` / `break_number` column and drop break/lunch from the period type
  enum.
- **Day bitmasks are 6 wide here** (`100000` = Day 1), so mask width is
  `cycle_length`, not a constant. The `Bitmask` value object must be constructed
  with an explicit width.
- **`periodsperweek` is a decimal** (`"6.0"`), not an integer. Use
  `decimal(4,1)`. aSc allows 0.5 for fortnightly lessons.
- **`capacity="*"`** is aSc's "unlimited" sentinel on both lessons and classrooms.
  Store as nullable, not as the literal string.
- **`grades` is present but unused** — 20 rows, and every class has `grade=""`.
  Skip it; do not build UI for it.
- **Teacher `short` and `color` are load-bearing**, and `gender="F"` is set on all
  20 teachers including ones named as men elsewhere in the file, so that field is
  unreliable data entry — import it, never display it.
- **`partner_id`** is aSc's external-system key, empty throughout. Carry it as a
  nullable passthrough column so re-export is lossless.
- **Encoding is `windows-1252`.** The importer must transcode to UTF-8 explicitly,
  and the exporter must write cp1252 with the matching declaration.
- The root element's `options` string (`groupstype1`,
  `lessonsincludeclasseswithoutstudents`, `handlestudentsafterlossons` etc.) and
  each section's `columns` list must be preserved verbatim on export — aSc uses
  them to decide how to read the file back.

### 5.3 Divisions and groups — the real structure

138 groups across 10 classes. `divisiontag` is the division number within a class;
`divisiontag="0"` with `entireclass="1"` is the whole-class group.

Divisions per class: F1A 3, F1B 3, F2A 1, F2B 1, F3A 5, F4A 7, F4B 7, F5A 6,
F5B 7, F5C 9.

Concretely:

```
F1A  tag 1 -> Group 1, Group 2          (generic halves)
     tag 2 -> Boys, Girls               (by gender)
     tag 3 -> French, Setswana          (language election)

F3A  tag 2 -> Agriculture, Add Maths, Eng Lit
     tag 3 -> Physics, Geography, Economics
     tag 4 -> Chemistry, Business Studies
     tag 5 -> Setswana, French, Accounting

F5A  tag 2 -> EL, PHY B, AG, CH A       (4-way)
     tag 5 -> BS1, SET, FR, ADD MATHS   (4-way)
     tag 6 -> MaE_Sim, MaC, MaE_Chisenga
```

Four findings that shape the schema:

1. **A class has many independent divisions, and they are not equal-sized splits.**
   A division is an *option block*: a named set of parallel groups, one per subject
   choice. F5C has nine. So `tt_divisions.parts_count` from §3 is wrong — a
   division has N named groups, and N varies per division (2, 3 or 4 here).
2. **Group names are subject choices, not slot labels.** `Physics`/`Geography`/
   `Economics` in one division is exactly the `Ph`/`Ge`/`ECO` stack seen in the
   screenshot. This is the join between the two artefacts.
3. **The same subject can appear twice in one division under different teachers** —
   `MaE_Sim` and `MaE_Chisenga` are both Extended Maths. So group identity cannot
   be derived from subject; it needs its own row, as aSc has it.
4. **128 of 138 groups have `studentids=""`.** Only the 10 whole-class groups list
   members. Division membership is carried instead by the 1223 `studentsubject`
   rows: a student is in the `Physics` group of F3A's tag-3 division because they
   have a `studentsubject` row for Physics. `seminargroup="-"` on 1223 of them means
   seminar sub-splitting is unused.

That last point resolves the membership question in §8 and inverts the import strategy:
**derive group membership from `studentsubject` × the group's subject, not from
`studentids`.** It also means the existing `timetable_groups` (hand-picked student
lists) are the wrong primitive, while the existing `student_subjects` table is the
right one and should feed division membership.

That table already fits. `database/migrations/2026_03_05_081623_create_student_subjects_table.php`
plus its two follow-ups give `student_subjects` the columns
`student_id, subject_id, teacher_id, class_id, academic_year_id, is_elective`
(`app/Models/StudentSubject.php:12`). The `teacher_id` column is what makes finding 3
tractable: `MaE_Sim` and `MaE_Chisenga` are the same `subject_id` distinguished by
`teacher_id`, so a group resolves to
`student_subjects WHERE class_id = ? AND subject_id = ? AND teacher_id = ?`.

`is_elective` looks like it should separate option-block subjects from core ones, but
**it does not** — see §5.6. Derive option structure from the election sets instead.

Consequence: **no student-membership data entry is needed.** If the school's
`student_subjects` rows are current, importing the aSc file reconstructs every
division and its membership from data the system already holds. Verify that
assumption against live data early in Phase 1 — it is the difference between a
one-command import and 128 groups of manual work.

### 5.4 Lesson and card format, confirmed

```xml
<lesson id="A4C1632FCF96F06C"
        classids="608494C21A9DB3B5,28B96DDC00F92C18,3D228A760EA9FE65"
        subjectid="D534EE8321F5F2C6" teacherids="8C867E9164E612C5"
        groupids="6DD2C9CF49D1609C,D5FF18C066AFA2CF,7EF58A0D5B0D0540"
        classroomids="262DF86DDBD61826,35E7D4DB86BE1A5F,…"
        periodspercard="2" periodsperweek="6.0"
        daysdefid="BE34A0A40B405EB1" weeksdefid="…" termsdefid="…"
        seminargroup="" capacity="*"/>

<card lessonid="802840801BB77227" period="7" days="010000"
      weeks="1" terms="1" classroomids="9E8E905058A080C7"/>
```

- Every one of the 10 sampled lessons has `periodspercard="2"` and
  `periodsperweek="6.0"` — doubles confirmed as the norm, three doubles per week.
- **Cross-class option lessons are normal**: `classids` lists F5A+F5B+F5C with three
  `groupids`, one group per class. One Accounting lesson serves the Accounting
  electors from all three Form 5 streams, taught by one teacher. This is why
  many-to-many `classids`/`groupids` is mandatory, not a nicety.
- `classroomids` on a **lesson** is the candidate set (up to 15 rooms = "anywhere");
  on a **card** it is the single resolved room. Two different meanings for the same
  attribute name — `tt_lesson_room` (ordered candidates) vs `tt_cards.room_id`.
- A `periodspercard="2"` double is stored as **two card rows** on consecutive
  periods (7 and 8), same `days` mask — not one row with a length. Placement must
  write and move them as a unit while storing them separately.
- `daysdefid` is usually "Any day" (`BE34A0A40B405EB1`) — the generator is free to
  choose the day, and the `daysdef` on the lesson is a *constraint*, while the
  `days` on the card is the *outcome*.
- `<classroomsupervisions>` exists and is empty. Duty rosters are out of scope.

### 5.5 Fixture

`docs/term 2 sample.xml` is the Phase 2 import fixture, the Phase 9 round-trip
fixture, and the shape reference for every phase's factories. It is a real file
with real staff
names and student names — keep it in `docs/`, and build the test fixture as an
anonymised reduction (3 classes, ~8 subjects, keep one 4-way division and one
cross-class option lesson) rather than committing the original into `tests/`.

### 5.6 Evidence from the live database

Checked against `school_erp` on 2026-08-07. This was the §8 gating question, and the
answer is the good one: **the derivation strategy in §5.3 works.**

- 167 students, 1564 `student_subjects` rows, **100% election coverage in every
  class** — F1A 12/12, F1B 13/13, F2A 14/14, F2B 14/14, F3A 18/18, F4A 21/21,
  F4B 18/18, F5A 23/23, F5B 14/14, F5C 20/20. No stream needs manual entry.
- `timetable_entries` is empty (0 rows), which confirms §7's decision to drop the
  legacy data migration.
- **Elections encode real option structure.** F1A has exactly 2 distinct subject
  sets, splitting French(6) + Setswana(6) = 12 — a clean partition matching XML
  division tag 3. F5A has 15 distinct sets across 23 students, with only Biology and
  Computer Science universal; within it Extended(17) + Core(6) = 23 and
  Esl(12) + Efl(11) = 23 are both clean partitions.
- **The decisive confirmation**: F5A Extended Mathematics splits by `teacher_id` into
  Faith Chisenga = 4 and Kunda Simukonda = 13, reproducing the XML group names
  `MaE_Chisenga` and `MaE_Sim` exactly. Same-subject group disambiguation is
  automatic.

Three corrections the importer has to carry:

1. **`is_elective` is unusable.** It is `0` on all 1564 rows, so it cannot discriminate
   core from option subjects. Infer option blocks from partition structure — a set of
   subjects whose elector counts sum to the class roll and whose membership does not
   overlap is a division.
2. **Names do not match across the two systems; codes nearly do.** The DB holds 20
   subjects against the XML's 31, and wording differs ("English As a Second Language"
   vs "English second language"), as does class-name case ("Form 1A" vs "FORM 1A").
   Match on `subjects.code` ↔ aSc `subject/@short` case-insensitively first (Acc, AddM,
   Ag, Bs, CS, Eco, EFL, EL, Esl, Fr, Geo, MaC, MaE, Set, T&T all line up), then fall
   back to normalised names for the five that differ (BIO/Bi, MATH/Ma, CHEM/Ch,
   PHY/Ph, Geo/Ge). Report unmatched entities rather than silently creating duplicates.
3. **Expect data-entry noise and report it, do not "fix" it.** F5C has singleton
   teacher splits — Business Studies (Onalethata) = 1, Esl (Odhiambo) = 1, Geography
   (Nkandela) = 1 — which would become one-student groups. Surface these in the import
   report for a human to judge. Separately, a per-class aggregate returned a class
   displaying as `Form 1Aa` with 0 students, but an exact-name lookup finds nothing;
   likely trailing whitespace in `classes.name`. Normalise on read; flag, don't rewrite.

---

## 6. Phases

Each phase is independently shippable and leaves the suite green. Do them in order —
later phases assume earlier schema.

**Ordering change from the first draft:** the XML importer moves from Phase 8 to
Phase 2. The school has a working aSc file with 138 groups, 128 students, 1223
subject elections and several hundred placements. Importing it is how every later
phase gets realistic test data, and it is the fastest route to something the school
recognises as their own timetable. Export stays late — it is only needed to hand
data back to aSc.

### Phase 1 — Schema and models
Create the `tt_*` tables, Eloquent models, factories, and the `Bitmask` value object.
Leave the legacy `timetable_*` tables in place and untouched.

- Add: `database/migrations/2026_08_XX_000001_create_tt_core_tables.php`
  (settings, periods, breaks, daysdefs, weeksdefs, termsdefs, rooms, subject/teacher meta),
  `..._000002_create_tt_division_group_tables.php`,
  `..._000003_create_tt_lesson_card_tables.php`,
  `..._000004_create_tt_constraint_tables.php`
- Add: `app/Models/Tt/{Setting,Period,BreakPeriod,DaysDef,WeeksDef,TermsDef,Division,Group,Room,Lesson,Card,Constraint,GenerationRun}.php`
  (`Break` is a PHP reserved word — `class Break` is a parse error, so the model for
  `tt_breaks` is `BreakPeriod`.)
- Add: `app/Support/Timetable/Bitmask.php` — explicit width, `intersects()`,
  `positions()`, `fromDayNumber()`, `toAscString()`.
- Add: `database/factories/Tt/*Factory.php` shaped like the real data: 6-day cycle,
  8 periods, doubles, one 4-way division.
- Add: `tests/Unit/Timetable/BitmaskTest.php` — width handling, intersection,
  6-day masks specifically.

Done when: `php artisan migrate` runs clean, `Bitmask` is unit-tested, and the
suite is green.

**Never run `migrate:fresh`, `migrate:refresh`, or `migrate:rollback` against the
live database** — `school_erp` holds 167 real students and 1564 elections, and every
`tt_*` migration must therefore be forward-only and purely additive. Tests are safe:
`phpunit.xml` pins `DB_CONNECTION=sqlite` / `DB_DATABASE=:memory:`, so `RefreshDatabase`
never reaches MySQL.

The live-data check this phase used to carry is already done — see §5.6.

### Phase 2 — aSc XML import
Bring `docs/term 2 sample.xml` in. This front-loads the realistic dataset and proves
the schema against a real file rather than against factories.

- Add: `app/Services/Timetable/Asc/{XmlImporter,IdMap,AscOptions}.php`
- Handle: cp1252 → UTF-8, `capacity="*"` → null, `periodsperweek="6.0"` → decimal,
  breaks as their own entity, 6-wide day masks, `partner_id` passthrough.
- Match aSc entities onto existing project rows where possible (subjects by name or
  code, teachers by name, classes by name) and report anything unmatched rather than
  silently creating duplicates. `IdMap` persists `asc_id` → local id.
- Derive division membership from `student_subjects` per §5.3; list any group that
  resolves to zero students so the admin can fix elections.
- Add: `app/Console/Commands/TimetableImportAsc.php` — `php artisan timetable:import-asc <file>`
  with `--dry-run` printing a diff summary, and `--json=<path>` writing the full
  report (the real file's unmatched list is longer than a terminal keeps).
- Add: `tests/Feature/Timetable/AscImportTest.php` against an anonymised reduction
  of the real file (§5.5), asserting counts, a cross-class option lesson, a 4-way
  division, and a double landing as two consecutive cards.

Done when: every entity in the real file either imports or is explained in the
report, and the reduced fixture's assertions pass. **Not** "zero unmatched" — §5.6
already established the DB has 20 subjects against the XML's 31, so a clean report
was never reachable. What matters is that nothing is silently dropped or invented.

**Status: done (2026-08-08).** See §9. The real file imports 10/10 lessons and
10/10 cards with nothing skipped; the 80 unmatched entities are all explained.

### Phase 3 — Read model over cards
Rewrite `TimetableService` to resolve schedules from `tt_cards`, keeping the JSON
payload compatible so the shipped Flutter apps and both Blade viewers keep working.

- Edit: `app/Services/TimetableService.php` — resolve date → bit position, filter
  cards by day/week/term mask intersection, merge consecutive cards of one lesson
  back into a single display block, interleave `tt_breaks`.
- Add: `app/Services/Timetable/ScheduleResolver.php` — date → (day_number,
  week_index, term_index) via `timetable_cycle_anchors` plus the new defs.
- Student resolution now runs through groups: a student sees a card if they are in
  one of its `tt_lesson_group` groups, or in a participating class for a whole-class
  lesson.
- Fix: `emptyPayload()` returns `days` as a Collection, and guard the empty-days case
  in `resources/views/timetable/viewer.blade.php:35,112` (gap 2.11).
- Edit: `tests/Feature/TimetableTest.php` — keep all 5 tests, repoint at `tt_*`.
- Add: `tests/Feature/Timetable/PayloadContractTest.php` — pins the exact payload key
  set so a future change cannot silently break the mobile apps.
- Add: a test that two students in *different* groups of the same division see
  different subjects in the same period. That is the case the current system cannot
  represent at all, and it is most of this school's senior timetable.

Done when: the imported real timetable renders correctly for a teacher, a
whole-class student, and an option-block student, and the contract test passes.

### Phase 4 — Lesson and card management (no generator)
CRUD for divisions, groups, global periods, defs, lessons, and manual card placement.
Replaces the current create-only forms and finally adds editing (gap 2.8, partly).

- Add: `app/Http/Controllers/Admin/Timetable/{SettingController,PeriodController,BreakController,DivisionController,GroupController,LessonController,CardController,RoomController}.php`
- Division editor is the centrepiece: create an option block on a class, name its
  parallel groups, and pick each group's subject + teacher. Membership shows as
  derived-from-elections with a manual override, never as blank pickers — F5C has
  nine divisions and hand-picking students across them is not viable.
- Add: `app/Services/Timetable/CardPlacementService.php` — `place()`, `move()`,
  `unplace()`, `lock()`. A `periods_per_card = 2` double is written, moved and
  removed as a unit across its two card rows (§5.4).
- Add: `app/Services/Timetable/ConflictChecker.php` — port the three working rules
  from `TimetableService::validateEntry()`, now bitmask-aware (two cards clash only
  if their day AND week AND term masks intersect) and group-aware (two cards clash
  for students only if their group memberships actually overlap — parallel groups of
  the same division must NOT clash, which is the whole point of an option block).
- Edit: `routes/admin.php:93-102` — replace the 9 ad-hoc routes with resource routes.
- Add: `tests/Feature/Timetable/CardPlacementTest.php`,
  `.../ConflictCheckerTest.php` — mask-intersection cases and, critically, that four
  parallel groups of one division placed in the same period raise no conflict while
  two lessons sharing a group do.

Done when: an admin can build a full cycle by hand, doubles move atomically, and
conflict tests cover teacher/class/room/student across day, week, term and group.

### Phase 5 — Constraints
The constraint table, an evaluator, and admin UI for availability grids.

- Add: `app/Services/Timetable/Constraints/` — one class per kind, all implementing a
  `Constraint` contract with `evaluate(Timetable $state): array` returning violations
  and a penalty. Start with the aSc set that matters most: `CardsPerDay`,
  `MaxGapsPerDay`, `NoGaps`, `TeacherAvailability`, `ClassAvailability`,
  `RoomAvailability`, `SubjectMaxPerDay`, `DoublePeriodRequired`,
  `MaxConsecutiveLessons`.
- Add: `app/Services/Timetable/ConstraintRegistry.php` — resolves rows to instances.
- Add: `tests/Unit/Timetable/Constraints/*Test.php` — one per constraint, table-driven.

Done when: each constraint has a unit test proving it fires on a violating state and
stays silent on a valid one.

### Phase 6 — Generator
Backtracking search with constraint propagation. Runs in a queued job, writes progress
to `tt_generation_runs`.

- Add: `app/Services/Timetable/Generator/{Generator,SearchState,DomainBuilder,Heuristics}.php`
- Approach: MRV (most-constrained lesson first) + forward checking on hard constraints,
  then a weighted local-search polish pass on soft constraints. Deterministic given a
  seed, stored on the run row so a result is reproducible.
- Doubles are a first-class unit in the search, not a post-hoc constraint: the domain
  for a `periods_per_card = 2` lesson is consecutive-period pairs, so a double can
  never be split across a break or the end of a day.
- Option blocks reduce the search usefully — the parallel groups of one division can
  and should occupy the same period, so treat a division as a single composite
  placement rather than N independent lessons competing for slots.
- Add: `app/Jobs/GenerateTimetable.php`
- Add: `tests/Feature/Timetable/GeneratorTest.php` — the anonymised fixture from §5.5
  must place 100% with zero hard violations and be reproducible across two runs with
  the same seed. Cap the search budget so the suite stays fast.
- Benchmark separately (not in CI) against the full imported file: ~10 streams,
  8 periods × 6 days, several hundred cards. Record the wall time in §9.

Done when: the fixture generates fully, twice, identically, inside the budget, and the
full-file benchmark time is recorded.

### Phase 7 — Grid editor
Drag-drop grid by class / teacher / room, with conflict shading and possible-slot
highlighting.

- Add: `resources/views/admin/timetable/grid.blade.php` + an Alpine component; the
  project already uses Blade + Tailwind + inline scripts, so stay with that rather than
  introducing a SPA framework.
- Match the reference layout in `docs/asc.png`: class rows, day-grouped period
  columns, subject short codes on per-subject colours, and **stacked sub-rows inside
  one cell** when a division splits the class. That stacking is the hardest part of
  the view and the most important — six of ten streams need it.
- Room and teacher are not shown in the class grid at this density; put them in a
  hover/expand detail, as aSc does.
- Add: `app/Http/Controllers/Admin/Timetable/GridController.php` — JSON endpoints for
  grid state, candidate slots for a card, and move.
- Add: `tests/Feature/Timetable/GridControllerTest.php`

Done when: the imported real timetable renders recognisably against `docs/asc.png`, a
card can be dragged between periods, illegal targets are visibly blocked before the
drop, and the move endpoint rejects anything the client let through.

### Phase 8 — Verification report and print
- Add: `app/Services/Timetable/VerificationService.php` — unplaced lessons, teacher
  load vs `max_lessons_per_day`, class gaps, room over-subscription, constraint
  violations by weight.
- Add: `resources/views/admin/timetable/verify.blade.php`
- Add: printable views per class / teacher / room, reusing the existing dompdf setup
  (`barryvdh/laravel-dompdf`, already a dependency — see
  `resources/views/pdf/partials/` for the house pattern).
- Add: `tests/Feature/Timetable/VerificationTest.php`

Done when: publishing is gated on a clean hard-constraint report, and each print view
renders for a seeded template.

### Phase 9 — aSc XML export
The import half shipped in Phase 2; this closes the loop so data can go back to aSc.

- Add: `app/Services/Timetable/Asc/XmlExporter.php`
- Write cp1252 with the matching declaration, preserve the root `options` string and
  each section's `columns` list verbatim, re-emit stored `asc_id` and `partner_id`,
  and render `capacity` as `*` where null (§5.2).
- Constraints do not survive the aSc XML round-trip in a usable form — document that
  as a known limit rather than half-exporting them.
- Add: `tests/Feature/Timetable/AscRoundTripTest.php` — import → export → import
  produces an identical card set, and a diff of the exported XML against the
  anonymised fixture shows only intended changes.

Done when: the round trip is card-identical and aSc opens the exported file without
complaint.

---

## 7. Decisions already taken

- New `tt_*` tables alongside the legacy ones; no destructive drop until Phase 9 is
  shipped and verified against real data. The legacy tables are read-only from Phase 3
  onward.
- **No data migration from `timetable_entries`.** The first draft planned one; the
  aSc export makes it pointless. The real timetable lives in aSc, so Phase 2 imports
  it and the legacy entries are simply superseded. This removes a migration, a test,
  and the guesswork of inferring lessons from placements.
- The public JSON payload shape is frozen. Both Flutter apps
  (`kweneng_teacher/lib/screens/timetable_screen.dart`,
  `kweneng_parent/lib/screens/timetable_screen.dart`) are already released against it,
  so any new field is additive and every existing key keeps its meaning. Phase 3's
  contract test enforces this.
- Bitmasks as aSc-style strings, wrapped in one value object with explicit width.
- Group membership derives from `student_subjects`; `tt_group_student` is override-only.
- Blade + Tailwind + Alpine for the grid, matching the rest of the app.
- Generator runs queued and seeded, never inline in a request.
- The rotating-cycle anchor feature stays; it layers over the new day resolution.
- `docs/term 2 sample.xml` and `docs/asc.png` stay in `docs/` as references; test
  fixtures are anonymised reductions, since both contain real staff and student names.

## 8. Open questions

Resolved since the first draft:

- ~~Week and term model~~ — `weeksdefs` has one entry ("All weeks") and `termsdefs`
  one ("Whole year"). The school cuts a **separate file per term** rather than using
  aSc's term bitmasks. So: build `tt_termsdefs` for round-trip fidelity, but model
  the school's actual workflow as one template per term. No A/B week UI.
- ~~Option-block membership~~ — carried by `studentsubject` (1223 rows), not by group
  `studentids` (empty on 128 of 138 groups). Derive from `student_subjects`; §5.3.
- ~~Existing production data~~ — moot, per §7. Nothing to migrate.
- ~~Are `student_subjects` current for the senior forms?~~ **Yes.** Verified against
  the live database on 2026-08-07; see §5.6. 167 of 167 students have elections, and
  `teacher_id` reproduces aSc's own group names. Phase 2's derivation is sound.

Still open:

1. **What the left-margin numbers in `docs/asc.png` mean** (~192 for F1A). Not in the
   XML. Ask the admin who built the file; until answered, do not invent a meaning for
   that column in the grid UI.
2. **Revisions.** The file is "Rev 5" — keep multiple revision snapshots in the
   system, or is one published + one draft enough? Cheap now as a `revision` column
   on `tt_settings`; default is published + draft.
3. **Substitutions.** aSc ships this as a separate product. In scope later, or out?
4. **Is the 8-period day plus one break complete?** The export has a single
   "Breaktime" at 10:10–10:30 before period 5, and periods running 07:30–13:10 with
   no lunch. Confirm there is genuinely no second break rather than an unexported one.
5. **Singleton groups in F5C** — three subjects have exactly one elector each (§5.6).
   Real one-student option groups, or data-entry slips? Ask before the import creates
   them.
6. **`docs/term 2 sample.xml` is a previous year's file.** Proven by the 2026-08-08
   dry run: of 128 students in the export, only 59 are still in the class it names.
   55 have been promoted since (FORM 1A→Form 2A ×15, Form 2A→Form 4A ×14, FORM 1B→
   Form 2B ×12, Form 2B→Form 4B ×12, plus two individual moves) and 14 have left the
   school. The Form 2→Form 4 jumps suggest it may be **two** years old, or that the
   school reorganised its streams.

   This costs the import nothing — membership derives from `student_subjects` at read
   time, so the student map is only used for explicit `studentids` overrides, of which
   the file has none. But it does mean the file is a **format** reference, not a
   **content** one, and its 138 groups / 49 divisions describe a roster that no longer
   exists. Get a current export before importing group structure for real.

## 8a. Answered

- **§8.1 (elections coverage)** — answered 2026-08-07, see §5.6. Gate passed 167/167.
- **Days-mask storage format** — Phase 1 left two conflicting conventions for
  `tt_daysdefs.days` (a JSON array in one place, aSc's comma string in another).
  Settled 2026-08-08 on aSc's own `"100000,010000,…"`: the column is `text` precisely
  to hold it, it round-trips to the export unchanged, and `DaysDef::masks()` /
  `WeeksDef::masks()` / `TermsDef::masks()` now decode it to `Bitmask[]`. Consumers
  must split on the comma before constructing a `Bitmask` — the value object strips
  non-`01` characters, so handing it the joined string silently concatenates six masks
  into one.

## 9. Session log

- **2026-08-07** — Surveyed existing implementation, wrote this document. No code
  changed. Next action: answer §8, then start Phase 1.
- **2026-08-07** — Reviewed `docs/asc.png` (school's live file, Term 2 Rev 5):
  confirmed 6-day rotating cycle with 8 periods/day, ten streams F1A–F5C, option
  blocks up to 4 groups deep in six of ten classes, doubles as the default lesson
  shape, per-subject colours, term-based workflow, and aSc's Test/Generate/
  Verification/Print IA. Recorded in §4.
- **2026-08-07** — Analysed `docs/term 2 sample.xml` (real aSc 2026.10.1 export).
  Added §5. Substantive changes to the plan:
  - XML import moved from Phase 8 to **Phase 2**; export is now Phase 9. Phases
    renumbered throughout — there are now 9.
  - Dropped the `timetable_entries` data migration entirely (§7).
  - Divisions are option blocks with N named subject-choice groups, N varying 2–4,
    up to 9 divisions per class. `parts_count` removed from the schema.
  - Group membership derives from `student_subjects`, which already has the
    `teacher_id` column needed to separate same-subject groups like
    `MaE_Sim` / `MaE_Chisenga` (`app/Models/StudentSubject.php:12`).
  - Breaks become their own table, not a period type.
  - `periods_per_week` is decimal; day masks are 6 wide; encoding is cp1252;
    `capacity="*"` means null; `asc_id`/`partner_id` on every importable entity.
  - One card = one period; doubles are two adjacent card rows moved as a unit.
  - Cross-class option lessons confirmed (one lesson serving F5A+F5B+F5C).
  - Lessons/cards in the sample are a partial slice (10 each) — format reference
    only; `docs/asc.png` remains the scale reference.

  Next action: answer §8.1 by checking live `student_subjects` coverage, then
  start Phase 1.

- **2026-08-07** — Ran the §8 gating check against live `school_erp`. Added §5.6.
  **The gate passed:** 167/167 students have elections (100% in all ten classes),
  1564 rows, and F5A Extended Maths splits by `teacher_id` into Chisenga=4 /
  Simukonda=13, reproducing the XML's `MaE_Chisenga` / `MaE_Sim` exactly. Group
  membership derivation is confirmed automatic; no manual data entry needed.
  Three corrections folded into the plan:
  - `is_elective` is `0` on all 1564 rows and **cannot** be used to tell core from
    option subjects (§5.3 corrected). Infer divisions from partition structure —
    F1A's French(6)+Setswana(6)=12 and F5A's Extended(17)+Core(6)=23 are the shape.
  - Subject and class names differ between DB and XML (20 subjects vs 31; "English
    As a Second Language" vs "English second language"; "Form 1A" vs "FORM 1A").
    Match on code first, normalised name second, report the rest.
  - F5C has three singleton teacher splits, logged as new §8.5.
  Also corrected Phase 1's "Done when": it called for `migrate:fresh --seed`, which
  against `school_erp` would destroy 167 students' records. `tt_*` migrations are
  forward-only; `timetable_entries` is empty (0 rows), confirming §7's no-migration
  decision.

  Next action: implement Phase 1.

- **2026-08-08** — **Phase 1 complete.** Four forward-only migrations
  (`2026_08_08_000001`–`000004`) create the 20 `tt_*` tables, with Eloquent models,
  factories, and the `App\Support\Timetable\Bitmask` value object. Settled the
  days-mask storage format that Phase 1 had left ambiguous (see §8a) and added
  `masks()` to the three defs models. `TtSchemaTest` covers the schema on sqlite;
  it is written to run against MariaDB too, which matters for `DECIMAL` — MariaDB
  returns `periods_per_week` as the string `"6.0"` where sqlite returns a float.

- **2026-08-08** — **Phase 2 complete.** aSc XML import, in
  `app/Services/Timetable/Asc/`: `XmlImporter` plus the `AscOptions`, `IdMap`,
  `EntityMatcher`, `ImportReport` and `DryRunComplete` supporting classes, driven by
  `php artisan timetable:import-asc`.

  Design decisions worth carrying forward:
  - **Every import is a new, inactive, unpublished `tt_settings` row.** The school
    re-exports as it works, so an import is a snapshot, not an edit — and a snapshot
    that lands as a draft cannot disturb the timetable teachers and parents are
    looking at. Setting-scoped rows duplicate per revision by design; `tt_groups`,
    `tt_divisions` and the two meta sidecars key on `asc_id` and do not.
  - **Nothing academic is ever created.** Subjects, teachers, classes and students are
    matched or reported, never invented — a duplicate "Biology" would split a
    subject's marks in two with no error anywhere. Matching is exact-after-
    normalisation only; no fuzzy matching, because anything confident enough to join
    "English second language" to "English As a Second Language" is also confident
    enough to join "Maths Core" to "Maths Extended".
  - **`--dry-run` performs a real import and rolls it back** via a signal exception.
    The only honest way to measure an import is to do one; every count in a dry-run
    report came from an actual write.
  - `break="5"` (aSc: the period the break precedes) stores as `after_period = 4`.
    The clock settles the off-by-one: period 4 ends 10:10, the break runs 10:10–10:30.

  **`AscImportTest`** — 25 tests against an anonymised, genuinely cp1252-encoded
  fixture (`tests/Fixtures/Timetable/asc-sample.xml`; real names never enter the
  repo). Full timetable suite: **48 passed, 302 assertions**. The encoding test was
  verified by sabotage rather than by assumption — deleting the transcode makes
  libxml reject the file, and transcoding twice drops exactly the accented student
  (6 matched of 7). An earlier draft of that test asserted on the teacher "Zoë
  Kgosi", which turned out to be vacuous: teachers fall back to a surname match, and
  "Kgosi" is pure ASCII. Students have no fallback, so they are the real proof.

  **Real-file dry run** against `docs/term 2 sample.xml` and live `school_erp`
  (rolled back; the four `tt_*` migrations were applied, and only those — the
  unrelated `2026_08_04_000001_recalculate_grades` is still pending on purpose):

  | | |
  |---|---|
  | imported | 10 lessons, 10 cards, 138 groups, 49 divisions, 15 rooms, 8 periods, 8 daysdefs, 1 break |
  | matched | 10/10 classes, 20/20 teachers |
  | unmatched | 11 subjects, 69 students |
  | skipped | **0 lessons, 0 cards** |

  Both unmatched sets are explained, and neither costs the import anything:
  - The **11 subjects** are the documented §5.6 gap (DB 20 vs XML 31 — Coordinated
    Science ×7, Development Studies, Commerce, Library, Supervised Study). None of
    them has a lesson in this partial export, which is why nothing was skipped.
  - The **69 students** are the file being a year or more out of date — see §8.6.
    This is the matcher working, not failing: it refuses to bind a student whose
    class has changed.

  Two report defects the real run exposed, both fixed: 128 near-identical "group has
  no lesson" warnings were burying the 11 actionable subject mismatches (now one
  aggregated line with per-class counts), and there was no way to keep a 200-line
  report — `ImportReport::toArray()` existed but was unwired, now behind `--json`.

  Next action: decide whether to commit a real (non-dry) import of this stale file,
  then start Phase 3.
