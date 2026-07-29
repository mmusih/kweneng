# Teacher Mobile and Parent Homework Backend

This source package provides the Laravel API required by the teacher Flutter app and the parent Flutter homework feed.

## Deployment

Run these commands from the Laravel application directory after replacing the source files:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Set `APP_URL` to the HTTPS public URL used by the Flutter applications. If Firebase push notifications are enabled, set `FIREBASE_CREDENTIALS` to the service-account JSON path relative to the Laravel project root.

Homework images are stored on Laravel's private `local` disk. Do not move them to the public disk. The API image routes authenticate and authorize every request.

## Authentication

All requests and responses use JSON except homework creation, which uses multipart form data.

### Teacher login

`POST /api/auth/teacher-login`

```json
{
  "email": "teacher@example.com",
  "password": "password",
  "device_name": "android-physical-device-id"
}
```

Use the returned token on subsequent requests:

```text
Authorization: Bearer TOKEN
Accept: application/json
```

The existing parent login remains `POST /api/auth/login` and remains restricted to parents.

## Teacher API

- `GET /api/teacher/dashboard` returns the active term, class-teacher classes, teaching assignments, counts, and pending parent absence notices.
- `GET /api/teacher/attendance/register?class_id=7&date=2026-06-19` returns the roster, saved statuses, and applicable parent absence notices.
- `POST /api/teacher/attendance/register` saves the full daily register.
- `GET /api/teacher/marks/sheet?class_id=7&subject_id=3&academic_year_id=2&term_id=5` returns eligible learners and saved marks.
- `POST /api/teacher/marks/sheet` saves mobile mark entry while enforcing term and exam-stage locks.
- `GET /api/teacher/homeworks` lists homework sent by the teacher.
- `POST /api/teacher/homeworks` sends photo homework.
- `GET /api/teacher/homeworks/{id}/image` streams the private image to its teacher.
- `DELETE /api/teacher/homeworks/{id}` soft-deletes the homework and removes its image.

### Save attendance

The request must include every learner currently in the class. The register screen should initialize everyone as `present`, and the teacher changes only exceptions.

```json
{
  "class_id": 7,
  "date": "2026-06-19",
  "students": [
    {"student_id": 101, "status": "present", "remarks": null},
    {"student_id": 102, "status": "excused", "remarks": "Parent reported illness"}
  ]
}
```

Allowed statuses are `present`, `absent`, `late`, and `excused`.

### Save marks

```json
{
  "class_id": 7,
  "subject_id": 3,
  "academic_year_id": 2,
  "term_id": 5,
  "marks": [
    {"student_id": 101, "midterm_score": 74, "endterm_score": null, "remarks": null}
  ]
}
```

The API preserves scores for any locked exam stage even if the client submits a value for that stage.

### Send photo homework

Send `POST /api/teacher/homeworks` as `multipart/form-data` with:

- `class_id` — required
- `subject_id` — required
- `client_request_id` — required UUID generated once when the teacher starts this send operation; reuse it for retries
- `image` — required JPG, PNG, WebP, HEIC, or HEIF; maximum 15 MB
- `title` — optional; defaults to the subject name plus “homework”
- `description` — optional, maximum 3,000 characters
- `due_date` — optional, today or later
- `is_graded` — optional boolean; defaults to false
- `total_marks` — required only when `is_graded` is true

The backend derives the academic year and active term. The mobile client must not supply or choose them.

## Parent homework API

- `GET /api/parent/homeworks` lists homework for every eligible child linked to the authenticated parent.
- `GET /api/parent/homeworks?student_id=101` filters the feed to one linked child.
- `GET /api/parent/homeworks/{id}/image` streams the private image after checking the parent-child, class, subject, and academic-year relationships.

Firebase notifications are sent only to parents of learners in the selected class who are assigned to the selected subject. If Firebase is not configured, homework is still stored and immediately available through the parent homework API.

## Parent absence integration

Parent absence reports do not create official attendance automatically. The report appears beside the learner in the web and mobile register. When the teacher saves `absent` or `excused`, the attendance row is linked to the parent notice and the notice is resolved. If the teacher saves `present` or `late`, the teacher's official status is retained and the notice is marked as seen.
