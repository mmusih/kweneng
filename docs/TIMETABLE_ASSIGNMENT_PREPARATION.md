# Lesson cards from teacher assignments

Open **Timetable → Create cards from teacher assignments**, then choose a class.

- Each active teacher–subject assignment appears once, labelled Core or Elective.
- Set singles and doubles per timetable cycle. **Apply pattern** updates that assignment; **Apply to this class** applies the same pattern to its other assignments. Two doubles and one single produce three cards occupying five periods.
- Drag an assignment tile into **Splits**, then drag its parallel assignment onto a split row. Matching occurrences are linked. Expand tiles to link individual singles or doubles when patterns differ. Drag back to the lesson area, or choose **Remove from split**, to unlink.
- Drag a tile or individual occurrence into **Joint classes** and choose another class with the same subject and teacher. Existing matching free occurrences are combined. If the other assignment has no occurrences yet, matching ones are prepared. Joining a split occurrence preserves its split link.
- Choose existing attendance groups where appropriate. Saving a split with automatic attendance creates a division and separate subject/teacher groups. This defines timetable attendance structure; it does not change student subject enrolments.
- Choose rooms on expanded occurrences, especially where parallel lessons need different rooms.
- **Create / update cards** saves all classes edited in this session. The cards appear in the timetable tray. Moving, returning or locking a split occurrence affects its entire linked row.
- The tray combines interchangeable prepared occurrences into one tile with a card count. Two identical doubles show as one double tile marked **2 cards**; a single remains a separate tile. Dragging places one occurrence and reduces the count; returning it increases the count. Different attendance, rooms, durations or split partners remain separate.
- Reopening restores prepared occurrences. Repeat saves retain lesson IDs. Placed occurrences must return to the tray before their structure changes or they are removed. Existing manually created lessons are left intact and are not generated a second time.
- Removed/inactive assignments appear under **Assignments needing attention**. Restore them, or return affected cards to the tray and remove their occurrences. A concurrent preparation save is rejected with a reload message.

## Database setup

Run the additive migration before using this screen:

```powershell
php artisan migrate --path=database/migrations/2026_09_17_000001_add_assignment_preparation_to_timetable.php --force
```

No existing lessons are converted or removed by the migration. Prepared occurrences have a stable UUID per timetable revision, a JSON list of class/subject/teacher source keys and group IDs, and an optional split UUID. Source keys survive the teacher-assignment editor replacing its database rows. Each prepared lesson represents one occurrence, preserving the existing single/double placement, conflict, workload and publication paths.

## Validation

```powershell
php artisan test --compact tests/Feature/Timetable
node --test tests/JavaScript/*.test.cjs
npm run build -- --configLoader runner
```

The runner config loader avoids an esbuild parent-directory permission failure in the restricted Windows workspace.
