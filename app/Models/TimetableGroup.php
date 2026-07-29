<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableGroup extends Model
{
    protected $fillable = [
        'academic_year_id',
        'subject_id',
        'name',
        'code',
        'description',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'timetable_group_class', 'timetable_group_id', 'class_id')
            ->withTimestamps();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'timetable_group_student', 'timetable_group_id', 'student_id')
            ->withTimestamps();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
