<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentTermSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'term_id',
        'attendance_total_days',
        'attendance_days_present',
        'punctuality',
        'behaviour',
        'remarks',
    ];

    protected $casts = [
        'attendance_total_days' => 'integer',
        'attendance_days_present' => 'integer',
    ];

    public const PUNCTUALITY_LABELS = ['Excellent', 'Good', 'Fair', 'Poor'];
    public const BEHAVIOUR_LABELS = ['Excellent', 'Good', 'Fair', 'Poor'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
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
