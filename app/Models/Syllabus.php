<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Syllabus extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'academic_year_id',
        'subject_id',
        'class_id',
        'title',
        'source',
        'file_path',
        'raw_text',
        'status',
        'notes',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(SyllabusTopic::class)->orderBy('sort_order')->orderBy('id');
    }

    public function schemes(): HasMany
    {
        return $this->hasMany(Scheme::class);
    }
}
