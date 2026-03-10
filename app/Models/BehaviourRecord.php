<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviourRecord extends Model
{
    use HasFactory;

    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MODERATE = 'moderate';
    public const SEVERITY_MAJOR = 'major';

    protected $fillable = [
        'student_id',
        'class_id',
        'teacher_id',
        'academic_year_id',
        'term_id',
        'record_date',
        'category',
        'severity',
        'incident',
        'action_taken',
        'remarks',
    ];

    protected $casts = [
        'record_date' => 'date',
    ];

    public static function severities(): array
    {
        return [
            self::SEVERITY_MINOR,
            self::SEVERITY_MODERATE,
            self::SEVERITY_MAJOR,
        ];
    }

    public static function categories(): array
    {
        return [
            'discipline',
            'respect',
            'fighting',
            'bullying',
            'disruption',
            'uniform',
            'homework',
            'language',
            'conduct',
            'other',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
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
}
