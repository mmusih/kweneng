<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkMark extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_LATE_SUBMISSION = 'late_submission';
    public const STATUS_COPIED = 'copied';
    public const STATUS_NOT_SUBMITTED = 'not_submitted';

    protected $table = 'homework_marks';

    protected $fillable = [
        'homework_id',
        'student_id',
        'submission_status',
        'marks_obtained',
        'percentage',
        'grade',
        'remarks',
        'status_updated_at',
        'status_updated_by',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'percentage' => 'decimal:2',
        'status_updated_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_SUBMITTED,
            self::STATUS_LATE_SUBMISSION,
            self::STATUS_COPIED,
            self::STATUS_NOT_SUBMITTED,
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_LATE_SUBMISSION => 'Late Submission',
            self::STATUS_COPIED => 'Copied',
            self::STATUS_NOT_SUBMITTED => 'Not Submitted',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_SUBMITTED => 'green',
            self::STATUS_LATE_SUBMISSION => 'yellow',
            self::STATUS_COPIED => 'orange',
            self::STATUS_NOT_SUBMITTED => 'red',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statusLabels()[$status] ?? 'Submitted';
    }

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function statusUpdatedBy()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }
}
