<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkMark extends Model
{
    use HasFactory;

    protected $table = 'homework_marks';

    protected $fillable = [
        'homework_id',
        'student_id',
        'marks_obtained',
        'percentage',
        'grade',
        'remarks',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
