<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_core',
        'is_active',
        'display_order'
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'class_subjects', 'subject_id', 'class_id') // Note: class_subjects (plural)
                   ->withPivot(['academic_year_id', 'max_marks', 'passing_marks'])
                   ->withTimestamps();
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects', 'subject_id', 'teacher_id') // Note: teacher_subjects (plural)
                   ->withPivot(['class_id', 'academic_year_id', 'is_primary'])
                   ->withTimestamps();
    }

    /**
     * Get the marks for the subject.
     */
    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    /**
     * Get the student subjects.
     */
    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isCore(): bool
    {
        return $this->is_core;
    }
}
