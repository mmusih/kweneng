# Exam absence

Each mark stores separate midterm_did_not_write and endterm_did_not_write booleans. Existing rows default to false: old blanks must not be inferred as absences.

A confirmed absence displays as **. The numerical score remains NULL, so it does not become zero, a grade, or an input to averages and rankings. An actual zero remains a scored result. Monitoring counts either a numerical score or a confirmed absence as completed, per assessment.

Teacher web and mobile entry, admin entry, parent mobile results, report cards, academic records, class summary sheets and marks monitoring use this status. Teacher and admin CSV imports accept ** for absence. Numeric replacements clear absence; explicit false with a null score clears the entry. Exam locks preserve both score and absence for teachers.

Deploy the database migration before the updated backend:

    php artisan migrate --path=database/migrations/2026_10_28_000001_add_exam_absence_to_marks.php --force

Rebuild and distribute the teacher and parent mobile applications to expose the changes on installed devices. Older clients keep numeric score fields unchanged; omitted absence flags are preserved when scores remain null.

Local verification: marks/comment/summary/report tests and API absence round-trip/lock tests; Flutter analysis for changed teacher and parent screens. The local MySQL migration was applied successfully on 2026-10-28.
