# Timetable classes and new academic years

Open **Timetable → Classes & new year** for the selected schedule.

## Final-exam exclusions

Tick classes that no longer attend lessons, then save participation. Untick them to
restore teaching. Exclusions apply to that schedule only; update both day and
afternoon schedules if needed. Published schedules and teacher loads update immediately.

Lessons, groups, assignments, students and saved placements are retained. Joint lessons
continue for participating classes. Excluded lessons do not occupy teachers or rooms,
appear in the grid/tray/public schedules/prints, or contribute to verification and loads.
Restoration checks saved placements and rolls back if they clash with work added since
exclusion. Move the conflicting active lesson or return it to the tray before retrying.
Editing the attendance or details of a shared lesson requires restoring its removed
participants first, to avoid silently discarding their saved associations.

## New-year copy

Create the destination academic year and classes first; the year must be open. Select
the destination year, load its classes, and explicitly choose a destination for every
source class. Stream letters need not match: Form 1A can become Form 2B. Select **Do not
copy** for graduating/leaving classes. Each destination can be used once per copy.

The copy is a separate unpublished timetable. It preserves cycle length, day structure,
lesson times, subjects, teachers, rooms, base rooms, and mapped group/division structure.
Set the new cycle anchor date and day. Temporarily excluded classes are included if
mapped and start enabled. Cards are unlocked and old assignment-preparation references
are cleared so copied lessons can be reviewed and edited independently.

Student enrolments, class/teacher subject assignments and student group overrides are
not copied or promoted. Custom timetable constraints are not copied. Review those,
subjects and group membership for the destination year, then verify and publish the
new schedule. The original schedule remains intact. Copy day and afternoon schedules
separately when both are needed.

## Deployment

Run the migration `2026_10_23_000001_add_excluded_classes_to_tt_settings.php` before
using this feature. It adds one nullable JSON column to `tt_settings` and does not
change existing schedule participation.

Regression coverage: `php vendor/phpunit/phpunit/phpunit tests/Feature/Timetable`.
