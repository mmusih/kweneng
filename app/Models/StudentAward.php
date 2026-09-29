<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAward extends Model
{
    protected $fillable = [
        'award_run_id', 'student_id', 'subject_id', 'award_title', 'subject_name_snapshot', 'citation', 'position', 'main_score',
        'tie_breaker_scores', 'class_name_snapshot', 'level_snapshot', 'admission_no_snapshot',
        'student_name_snapshot', 'selection_source', 'override_reason', 'certificate_reference',
    ];

    protected $casts = ['tie_breaker_scores' => 'array', 'main_score' => 'decimal:2'];

    public function run()
    {
        return $this->belongsTo(AwardRun::class, 'award_run_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
