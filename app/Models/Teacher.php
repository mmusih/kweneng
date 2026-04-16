<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subject', 'teacher_id', 'subject_id')
            ->withPivot(['class_id', 'academic_year_id', 'is_primary'])
            ->withTimestamps();
    }

    public function classes()
    {
        return $this->belongsToMany(ClassModel::class, 'teacher_subject', 'teacher_id', 'class_id')
            ->withPivot(['subject_id', 'academic_year_id', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Get the marks entered by this teacher.
     */
    public function marks()
    {
        return $this->hasMany(Mark::class, 'teacher_id');
    }

    public function libraryBorrowings()
    {
        return $this->hasMany(LibraryBorrowing::class);
    }
}
