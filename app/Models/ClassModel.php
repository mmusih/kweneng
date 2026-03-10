<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'level',
        'academic_year_id',
        'class_teacher_id',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'current_class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function classTeacher()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    public function historyRecords()
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withPivot(['academic_year_id', 'max_marks', 'passing_marks'])
            ->withTimestamps();
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'class_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects', 'class_id', 'teacher_id')
            ->withPivot(['subject_id', 'academic_year_id', 'is_primary'])
            ->withTimestamps();
    }

    public function studentHistories()
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }

    public function marks()
    {
        return $this->hasMany(Mark::class, 'class_id');
    }

    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class, 'class_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    public function punctualities()
    {
        return $this->hasMany(Punctuality::class, 'class_id');
    }

    public function behaviourRecords()
    {
        return $this->hasMany(BehaviourRecord::class, 'class_id');
    }
}
