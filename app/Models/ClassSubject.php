<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    use HasFactory;

    protected $table = 'class_subjects'; // Explicit table name

    protected $fillable = [
        'class_id',
        'subject_id',
        'academic_year_id',
        'max_marks',
        'passing_marks',
        'remarks'
    ];

    protected $casts = [
        'max_marks' => 'decimal:2',
        'passing_marks' => 'integer',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id'); // Explicit foreign key
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id'); // Explicit foreign key
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id'); // Explicit foreign key
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class, 'class_id', 'class_id')
                   ->where('subject_id', $this->subject_id)
                   ->where('academic_year_id', $this->academic_year_id);
    }
}
