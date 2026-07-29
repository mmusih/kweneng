<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassModel;
use App\Models\ParentAbsenceNotice;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceRegisterService
{
    public function applicableNotices(ClassModel $class, CarbonInterface|string $date): Collection
    {
        $dateString = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return ParentAbsenceNotice::query()
            ->with(['parent.user'])
            ->whereHas('student', fn ($query) => $query->where('current_class_id', $class->id))
            ->whereDate('absence_date', '<=', $dateString)
            ->where(function ($query) use ($dateString) {
                $query->whereDate('absence_date', $dateString)
                    ->orWhere(function ($rangeQuery) use ($dateString) {
                        $rangeQuery->whereNotNull('expected_return_date')
                            ->whereDate('expected_return_date', '>', $dateString);
                    });
            })
            ->latest('created_at')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');
    }

    public function save(
        ClassModel $class,
        Teacher $teacher,
        Term $term,
        CarbonInterface|string $date,
        array $rows,
        User $user
    ): Collection {
        $dateString = $date instanceof CarbonInterface ? $date->toDateString() : $date;
        $studentIds = collect($rows)->pluck('student_id')->map(fn ($id) => (int) $id);

        if ($studentIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'students' => 'Each learner may appear only once in the register.',
            ]);
        }

        $students = Student::query()
            ->where('current_class_id', $class->id)
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        if ($students->count() !== $studentIds->count()) {
            throw ValidationException::withMessages([
                'students' => 'One or more learners do not belong to this class.',
            ]);
        }

        $notices = $this->applicableNotices($class, $dateString);

        return DB::transaction(function () use ($rows, $students, $notices, $class, $teacher, $term, $dateString, $user) {
            $saved = collect();

            foreach ($rows as $row) {
                $student = $students->get((int) $row['student_id']);
                /** @var ParentAbsenceNotice|null $notice */
                $notice = $notices->get($student->id);
                $confirmedFromNotice = $notice && in_array($row['status'], [
                    Attendance::STATUS_ABSENT,
                    Attendance::STATUS_EXCUSED,
                ], true);

                $attendance = Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'attendance_date' => $dateString,
                    ],
                    [
                        'class_id' => $class->id,
                        'teacher_id' => $teacher->id,
                        'academic_year_id' => $class->academic_year_id,
                        'term_id' => $term->id,
                        'parent_absence_notice_id' => $confirmedFromNotice ? $notice->id : null,
                        'status' => $row['status'],
                        'source' => $confirmedFromNotice
                            ? Attendance::SOURCE_PARENT_NOTICE
                            : Attendance::SOURCE_TEACHER,
                        'remarks' => $row['remarks'] ?? null,
                    ]
                );

                if ($notice) {
                    $confirmedFromNotice
                        ? $notice->markResolved($user)
                        : $notice->markSeen($user);
                }

                $saved->push($attendance);
            }

            return $saved;
        });
    }
}
