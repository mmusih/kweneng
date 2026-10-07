# Subject Combination Planner

Open **Admin dashboard → Subject Combination Planner**. Only active administrators can access its pages or submit changes. Intended for upper-class option planning, it lets the administrator choose any class or combination of classes in the selected academic year.

## Workflow

1. Load the timetable whose academic year and rooms should be used.
2. Select the classes, a plan name and 2–8 option blocks.
3. Mark subjects as compulsory, optional or excluded. Compulsory subjects never enter option blocks.
4. Set 1–4 groups per option subject. Multiple groups can occupy different blocks or split a large subject within one block. A plan supports up to 40 groups.
5. Select suitable rooms and optionally enter demand estimates. If omitted, room candidates are inferred from existing lessons for the selected classes, or provisionally from all timetable rooms. Missing room requirements and learner demand are flagged.
6. Generate up to three distinct alternatives. Fewer may be returned when fewer distinct structures are found. They are saved as drafts.
7. Compare the blocks, open a draft, move groups using block selectors, then save and rescore. Reopen its setup to change classes, subjects, group counts, rooms or estimates and regenerate.
8. Accept only after reviewing the findings. Acceptance saves a planning decision; it does not publish a timetable or modify subject enrolments. Editing an accepted arrangement returns it to draft.

## How suggestions are assessed

Each block is simultaneous. A learner wanting Biology and Geography needs those subjects in different blocks, unless alternative groups allow the learner to select a different offering.

The service reads the existing subjects, active teacher assignments scoped to class/year, timetable rooms/capacities, lesson-room relationships and learner subject enrolments. It stores references and planning settings, not duplicate school resource records.

The bounded search tries alternative arrangements and improves them by moving groups. Equivalent arrangements that only rename blocks are deduplicated. Ranking prioritises:

1. Fewest hard conflicts: missing/deactivated subjects, empty blocks, teachers without a distinct assignment, and suitable rooms without enough seats.
2. Fewest learners whose recorded selections cannot fit distinct blocks.
3. The smallest difference in estimated learner loads between blocks.

Teacher and room allocation use maximum bipartite matching. A teacher or room can be reused across different blocks but cannot serve two groups in the same block. Learner matching also considers repeated subject offerings and distinct blocks.

The displayed percentage is the proportion of learners with option records whose choices fit. **It is not a composite feasibility score.** No learner data means no percentage. Resource conflicts remain visible alongside the score and prevent acceptance.

Demand uses the larger of recorded enrolment and the administrator's estimate. Group capacity checks use the larger of an even demand split and a provisional learner allocation. This is conservative and may reject an arrangement for which a better global allocation exists. No claim of an optimal solution is made.

## Limits and review

- Existing enrolments are a demand proxy, not a preference survey. Learners without selected-option records do not enter the fit percentage.
- Specialist-room requirements must be confirmed; room names are not used to guess suitability.
- Teacher candidates come from assigned teachers in the selected cohort, not an inferred qualification list.
- These checks assess simultaneous option groups. Weekly lesson frequencies, teacher availability outside the blocks, other cohorts using the same room, and compulsory-subject scheduling still require timetable verification.
- Saved list scores describe the most recent saved assessment. Opening, saving and accepting a plan re-evaluates current resources. Acceptance requires acknowledgement of clashes and assumptions, and rejects hard resource/block conflicts.
- For additional years or cohorts, generate separate plans. Several accepted alternatives may be kept; acceptance does not select a school-wide live timetable.

## Deployment and verification

Apply `database/migrations/2026_10_07_000001_add_hr_role_and_subject_option_plans.php` and rebuild the assets. This migration also adds the HR login role. Existing teacher/room/learner records are reused.

`SubjectOptionPlannerTest` covers teacher and room contention, alternative resource assignments, insufficient capacity, absent learner data, repeated subject offerings and ranking. `SubjectOptionPlanTest` covers selectable classes, compulsory exclusion, generation, editing, acceptance, stale-resource checks, forged groups, role boundaries, HR account creation and login. Existing dashboard and HR/finance workflow suites provide regression coverage.

The migration passed the isolated SQLite tests and was subsequently applied successfully to the local school MySQL database on 7 October 2026.
