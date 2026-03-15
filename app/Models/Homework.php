<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    use HasFactory;

    protected $table = 'homeworks';

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'term_id',
        'title',
        'description',
        'total_marks',
        'assigned_date',
        'due_date',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'assigned_date' => 'date',
        'due_date' => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
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

    public function homeworkMarks()
    {
        return $this->hasMany(HomeworkMark::class);
    }
}
