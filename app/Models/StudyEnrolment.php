<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyEnrolment extends Model
{
    protected $fillable = ['term_id', 'student_id', 'recorded_by'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
