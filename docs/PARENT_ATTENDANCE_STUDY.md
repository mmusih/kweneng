# Parent attendance and study

The child header reads today's teacher register from `/api/parent/attendance/today`.
It refreshes every 30 seconds while visible, on returning to the app, and on pull-to-refresh.
Only children linked to the signed-in parent are returned. Attendance is never read from the offline dashboard cache.

- Present or late: green **In school: 07:30–13:10**, or **07:30–15:15** for study.
- Absent: red **Absent**. Excused: red **Absent (excused)**.
- No register entry today: neutral **Attendance not marked yet**.
- Outside the scheduled school hours: neutral **Present today** with the recorded day's hours; the register does not confirm actual checkout.
- Connection failure or stale response: neutral **Attendance unavailable**.

## Study setup

Open **After-school Study List** in the admin portal.

1. Select the **Study attendance term**, which determines when the rules and voluntary enrolments apply.
2. Select a **Results term** (that term or an earlier one) and **Mid-term** or **End of term**, then save.
3. Maintain the existing form/class thresholds. A class rule takes precedence over its form rule. Overall and subject conditions are combined with OR, as before.
4. Add students who choose to stay under **Voluntary study**. They remain until 15:15 even without qualifying marks or a study rule. Removing voluntary enrolment does not exempt a student who still meets the rules.

The default assessment is end-of-term for the selected attendance term. Review this selection when upgrading: the previous report used a combined mid/end-term average.
Only the selected assessment is used; missing scores are ignored, not converted to zero or substituted with the other assessment. Voluntary enrolment expires with its term.
Rules use each student's current class in the attendance term's academic year; the selected results term supplies that student's marks, including when their class has changed.

## Server rollout

Deploy the updated Laravel code and run `php artisan migrate --force` through the normal server release procedure.
The migration creates `study_retention_settings` and `study_enrolments`. Clear/rebuild the server route cache if enabled.
The teacher attendance service also includes the corrected parent-notice flag and date matching for register updates.

Deploy the server changes before distributing parent APK **1.1.3+11**. An APK alone cannot supply this feature: until the endpoint is available, its header displays **Attendance unavailable**.
No live-server migration or deployment is performed by the local build.
