<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentAbsenceNotice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'student_id',
        'absence_date',
        'expected_return_date',
        'reason',
        'note',
        'status',
        'seen_at',
        'resolved_at',
        'seen_by',
        'resolved_by',
    ];

    protected $casts = [
        'absence_date' => 'date',
        'expected_return_date' => 'date',
        'seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function seenBy()
    {
        return $this->belongsTo(User::class, 'seen_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class, 'parent_absence_notice_id');
    }

    public function markSeen(?User $user = null): void
    {
        $this->forceFill([
            'status' => $this->status === 'resolved' ? 'resolved' : 'seen',
            'seen_at' => $this->seen_at ?? now(),
            'seen_by' => $this->seen_by ?? $user?->id,
        ])->save();
    }

    public function markResolved(?User $user = null): void
    {
        $this->forceFill([
            'status' => 'resolved',
            'seen_at' => $this->seen_at ?? now(),
            'seen_by' => $this->seen_by ?? $user?->id,
            'resolved_at' => now(),
            'resolved_by' => $user?->id,
        ])->save();
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'seen' => 'Seen',
            'resolved' => 'Resolved',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'yellow',
            'seen' => 'blue',
            'resolved' => 'green',
            default => 'gray',
        };
    }
}
