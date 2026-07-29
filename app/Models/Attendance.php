<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_EXCUSED = 'excused';

    protected $fillable = [
        'student_id',
        'class_id',
        'teacher_id',
        'academic_year_id',
        'term_id',
        'attendance_date',
        'status',
        'remarks',
        'parent_absence_notice_id',
        'recorded_from_parent_notice',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'recorded_from_parent_notice' => 'boolean',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_ABSENT,
            self::STATUS_LATE,
            self::STATUS_EXCUSED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_EXCUSED => 'Excused',
        ];
    }

    public static function statusCodes(): array
    {
        return [
            self::STATUS_PRESENT => 'P',
            self::STATUS_ABSENT => 'A',
            self::STATUS_LATE => 'L',
            self::STATUS_EXCUSED => 'E',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statusLabels()[$status] ?? 'Unmarked';
    }

    public static function statusCode(?string $status): string
    {
        return self::statusCodes()[$status] ?? '';
    }

    public static function attendanceCountStatuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_ABSENT,
            self::STATUS_LATE,
            self::STATUS_EXCUSED,
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function parentAbsenceNotice()
    {
        return $this->belongsTo(ParentAbsenceNotice::class, 'parent_absence_notice_id');
    }
}
