<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Homework extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'homeworks';

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'term_id',
        'title',
        'description',
        'is_graded',
        'client_request_id',
        'total_marks',
        'assigned_date',
        'due_date',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'attachment_size',
        'image_disk',
        'image_path',
        'image_original_name',
        'image_mime_type',
        'image_size',
        'published_at',
        'attachment_deleted_at',
        'attachment_deleted_by',
        'attachment_deleted_reason',
        'attachment_storage_released_bytes',
    ];

    protected $casts = [
        'is_graded' => 'boolean',
        'total_marks' => 'decimal:2',
        'assigned_date' => 'date',
        'due_date' => 'date',
        'attachment_size' => 'integer',
        'image_size' => 'integer',
        'published_at' => 'datetime',
        'attachment_deleted_at' => 'datetime',
        'attachment_storage_released_bytes' => 'integer',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
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

    public function homeworkMarks()
    {
        return $this->hasMany(HomeworkMark::class);
    }

    public function parentReads()
    {
        return $this->hasMany(HomeworkParentRead::class);
    }

    public function attachmentDeletedBy()
    {
        return $this->belongsTo(User::class, 'attachment_deleted_by');
    }

    public function hasMarksConfigured(): bool
    {
        return $this->total_marks !== null && (float) $this->total_marks > 0;
    }

    public function hasAttachment(): bool
    {
        return filled($this->attachment_path) || filled($this->image_path);
    }

    public function attachmentDisk(): string
    {
        return $this->attachment_path ? 'local' : ($this->image_disk ?: 'local');
    }

    public function attachmentStoragePath(): ?string
    {
        return $this->attachment_path ?: $this->image_path;
    }

    public function attachmentOriginalName(): ?string
    {
        return $this->attachment_original_name ?: $this->image_original_name;
    }

    public function attachmentMime(): ?string
    {
        return $this->attachment_mime ?: $this->image_mime_type;
    }

    public function attachmentSize(): int
    {
        return (int) ($this->attachment_size ?: $this->image_size ?: 0);
    }

    public function attachmentWasPurged(): bool
    {
        return $this->attachment_deleted_at !== null;
    }

    public function hasImage(): bool
    {
        return filled($this->image_path) || filled($this->attachment_path);
    }
}
