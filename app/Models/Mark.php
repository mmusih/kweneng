<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'class_id',
        'teacher_id',
        'academic_year_id',
        'term_id',
        'midterm_score',
        'endterm_score',
        'midterm_did_not_write',
        'endterm_did_not_write',
        'grade',
        'remarks',
    ];

    protected $casts = [
        'midterm_did_not_write' => 'boolean',
        'endterm_did_not_write' => 'boolean',
        'midterm_score' => 'decimal:2',
        'endterm_score' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Mark $mark) {
            foreach (['midterm', 'endterm'] as $exam) {
                if ($mark->{$exam.'_score'} !== null) {
                    $mark->{$exam.'_did_not_write'} = false;
                }
            }
        });
    }

    public function assessmentRecorded(string $assessment): bool
    {
        return $this->{$assessment.'_score'} !== null || (bool) $this->{$assessment.'_did_not_write'};
    }

    public function assessmentDisplay(string $assessment): string
    {
        return $this->{$assessment.'_did_not_write'} ? '**' : ($this->{$assessment.'_score'} ?? '');
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
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

    // Helper Methods
    
    /**
     * Get the calculated average of midterm and endterm scores
     */
    public function getAverageAttribute(): ?float
    {
        if ($this->midterm_score === null && $this->endterm_score === null) {
            return null;
        }
        
        $total = 0;
        $count = 0;
        
        if ($this->midterm_score !== null) {
            $total += $this->midterm_score;
            $count++;
        }
        
        if ($this->endterm_score !== null) {
            $total += $this->endterm_score;
            $count++;
        }
        
        return $count > 0 ? $total / $count : null;
    }

    /**
     * Scope for getting marks by academic year
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    // Scopes
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForTerm($query, $termId)
    {
        return $query->where('term_id', $termId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}
