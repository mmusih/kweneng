<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardRun extends Model
{
    protected $fillable = [
        'award_category_id', 'type', 'title', 'academic_year_id', 'term_id', 'scope_type',
        'class_id', 'level', 'positions', 'cutoff_percentage', 'calculation_mode',
        'missing_marks_policy', 'tie_policy', 'calculation_config', 'generation_summary',
        'award_date', 'status', 'parent_visible', 'created_by', 'published_by', 'published_at',
    ];

    protected $casts = [
        'calculation_config' => 'array', 'generation_summary' => 'array', 'award_date' => 'date',
        'published_at' => 'datetime', 'parent_visible' => 'boolean', 'cutoff_percentage' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function awards()
    {
        return $this->hasMany(StudentAward::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
