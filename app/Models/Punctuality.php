<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Punctuality extends Model
{
    use HasFactory;

    public const STATUS_ON_TIME = 'on_time';
    public const STATUS_LATE = 'late';
    public const STATUS_VERY_LATE = 'very_late';
    public const STATUS_ABSENT = 'absent';

    protected $fillable = [
        'student_id',
        'class_id',
        'teacher_id',
        'academic_year_id',
        'term_id',
        'record_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ON_TIME,
            self::STATUS_LATE,
            self::STATUS_VERY_LATE,
            self::STATUS_ABSENT,
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
}