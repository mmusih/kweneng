# Schemes of Work / Syllabus Progress Feature

## Design decision
HOD does **not** replace the teacher role. A user remains `teacher` or `headmaster` in `users.role`, and can additionally be assigned as HOD through the new `department_user` table.

## What this update adds

### Admin
- Manage departments from `/admin/departments`.
- Assign academic staff to departments as:
  - Teacher
  - HOD
  - Assistant HOD
  - Observer

### Teacher web
- Teachers can open `/teacher/schemes`.
- Create a scheme from a real `teacher_subjects` assignment.
- Paste syllabus text and import topics/subtopics.
- Add topics manually.
- Drag topics from the Topic Bank into terms and weeks.
- Save plan.
- Track progress by topic/subtopic.
- Submit scheme to HOD.

### HOD web
- HODs use `/teacher/hod/schemes`.
- HOD sees department-level syllabus progress.
- HOD can approve schemes or request changes.
- HOD cannot tick completion for teachers.

### Teacher mobile API
Under `/api/teacher`:
- `GET /schemes`
- `GET /schemes/{scheme}`
- `PATCH /scheme-items/{item}`
- `PATCH /scheme-subtopics/{subtopic}/toggle`

## Installation steps

1. Copy the files into the Laravel project root, preserving paths.

2. Run migrations:

```bash
php artisan migrate
```

3. Clear caches:

```bash
php artisan optimize:clear
```

4. In the admin dashboard, open:

```text
/admin/departments
```

5. Create departments, for example:

```text
Mathematics
Sciences
Languages
Humanities
Commerce
```

6. Assign teachers to their departments. For the HOD, add an assignment with:

```text
role_in_department = HOD
```

7. Teacher opens:

```text
/teacher/schemes
```

8. Teacher creates a scheme, imports/pastes topics, drags topics into terms/weeks, then submits to HOD.

9. HOD opens:

```text
/teacher/hod/schemes
```

## Notes

- This first build does not require PDF/DOCX extraction packages. It supports manual topic entry and paste-text import immediately.
- PDF/DOCX + AI extraction can be added safely later.
- Progress is calculated from topic/subtopic completion.
- Expected progress is calculated from `terms.start_date`, `terms.end_date`, and the planned week numbers.
- A teacher must already have `TeacherSubject` assignments for scheme creation.
