<?php

namespace App\Models\Tt;

use App\Models\ClassModel;
use App\Models\Student;
use Database\Factories\Tt\GroupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Normal group membership is DERIVED from student_subjects, matched on
 * class_id + subject_id + teacher_id — it is not stored here. The
 * tt_group_student pivot (the students() relation below) is an OVERRIDE list
 * only, used when a student must be pinned to or excluded from a group that
 * the derivation would not produce. Phase 2 resolves membership as the
 * derived set with these overrides applied, so treat an empty students()
 * relation as "no overrides", never as "no members".
 */
class Group extends Model
{
    use HasFactory;

    protected $table = 'tt_groups';

    protected $fillable = [
        'class_id',
        'tt_division_id',
        'name',
        'shared_key',
        'entire_class',
        'asc_id',
        'partner_id',
    ];

    protected $casts = [
        'entire_class' => 'boolean',
    ];

    protected static function newFactory(): GroupFactory
    {
        return GroupFactory::new();
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'tt_division_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'tt_group_student', 'tt_group_id', 'student_id')
            ->withTimestamps();
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'tt_lesson_group', 'tt_group_id', 'tt_lesson_id')
            ->withTimestamps();
    }

    public function scopeEntireClass(Builder $query): Builder
    {
        return $query->where('entire_class', true);
    }

    public function scopeInDivision(Builder $query, int $divisionId): Builder
    {
        return $query->where('tt_division_id', $divisionId);
    }
}
