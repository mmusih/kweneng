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
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'current_class_id');
    }
    
    /**
     * Get the academic year for this class.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
    
    /**
     * Get history records for this class.
     */
    public function historyRecords()
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }
    
    /**
     * Get class subjects for this class.
     */
    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    /**
     * Get subjects assigned to this class.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
                   ->withPivot(['academic_year_id', 'max_marks', 'passing_marks'])
                   ->withTimestamps();
    }

    /**
     * Get teacher assignments for this class.
     */
    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'class_id');
    }

    /**
     * Get teachers teaching this class.
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects', 'class_id', 'teacher_id')
                   ->withPivot(['subject_id', 'academic_year_id', 'is_primary'])
                   ->withTimestamps();
    }
    
    /**
     * Get students with class history for this class.
     */
    public function studentHistories()
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }
    
    /**
     * Get the marks for this class.
     */
    public function marks()
    {
        return $this->hasMany(Mark::class, 'class_id');
    }

    /**
     * Get the student subjects for this class.
     */
    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class, 'class_id');
    }
}
