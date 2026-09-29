# Lesson cards from teacher assignments

Open **Timetable â†’ Create cards from teacher assignments**, then choose a class.

- Each active teacherâ€“subject assignment appears once in a searchable row, with placed and tray counts.
- Set singles and doubles per timetable cycle. **Save** applies edited counts automatically. Under **Cards**, **Apply counts** previews that assignment and **Use counts for class** applies the same counts to the class. Two doubles and one single produce three cards occupying five periods. Existing individual cards count toward the totals; increasing counts adds only the extra cards.
- Open **Cards** under an assignment and choose **Select joint classes** on a lesson card. Choose another class with the same subject and teacher. Existing matching free occurrences are combined. If the other assignment has no occurrences yet, matching ones are prepared. The same shared lesson appears when loading any participating class, without creating another copy. Repeat to add more classes, or use **Separate classes** to separate an unplaced joint card.
- **Select joint classes for all cards** applies the join to the assignment's prepared occurrences. Placed cards must return to the tray before their joint attendance changes.
- Split setup stays in the timetable's dedicated controls. This screen preserves existing split links and can use existing attendance groups; it has no separate split or joint-class panels.
- Choose rooms on expanded occurrences, especially where parallel lessons need different rooms.
- **Save** updates all classes edited in this session and refreshes the timetable tray. **Save & timetable** also closes the editor. Moving, returning or locking an existing split occurrence affects its entire linked row.
- The tray combines interchangeable prepared occurrences into one tile with a card count. Two identical doubles show as one double tile marked **2 cards**; a single remains a separate tile. Dragging places one occurrence and reduces the count; returning it increases the count. Different attendance, rooms, durations or split partners remain separate.
- Reopening restores prepared occurrences. Repeat saves retain lesson IDs. Placed occurrences must return to the tray before their structure changes or they are removed. Existing manually created lessons are left intact and are not generated a second time.
- Removed/inactive assignments appear under **Assignments needing attention**. Restore them, or return affected cards to the tray and remove their occurrences. A concurrent preparation save is rejected with a reload message.

## Changing required counts

Right-click a tray or grid card and choose **Change required count…**, or use the button in the information area. The dialog shows required, placed and remaining cards and previews the new period total. **Required = placed + remaining**. A double is one card using two periods.

Changing the requirement is explicit; returning a card to the tray does not change it. A reduction removes only spare demand and cannot go below the placed count. Teacher–subject assignments stay unchanged. Use **Undo required-count change** to reverse the last count edit while the timetable remains unchanged. Stale edits are rejected instead of overwriting newer work. Zero-count definitions are retained for immediate restoration; a later assignment preparation save may clean up unused prepared definitions.

Teacher load summaries show required and scheduled periods separately. Required periods change only when lesson demand changes, while placement changes scheduled periods.

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
