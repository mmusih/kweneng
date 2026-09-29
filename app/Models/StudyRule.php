<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyRule extends Model
{
    protected $fillable = [
        'academic_year_id', 'term_id', 'scope_type', 'scope_value',
        'overall_enabled', 'overall_threshold', 'subject_enabled', 'subject_threshold',
    ];

    protected $casts = [
        'overall_enabled' => 'boolean',
        'subject_enabled' => 'boolean',
        'overall_threshold' => 'decimal:2',
        'subject_threshold' => 'decimal:2',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
