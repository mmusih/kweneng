<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Requisition extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'reference',
        'requested_by',
        'academic_year_id',
        'term_id',
        'title',
        'department',
        'priority',
        'status',
        'needed_by',
        'reason',
        'inventory_notes',
        'handled_by',
        'acknowledged_at',
        'approved_at',
        'ordered_at',
        'fulfilled_at',
        'cancelled_at',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'acknowledged_at' => 'datetime',
        'approved_at' => 'datetime',
        'ordered_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $requisition) {
            if (! filled($requisition->reference)) {
                $requisition->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'REQ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_ORDERED => 'Ordered',
            self::STATUS_FULFILLED => 'Fulfilled',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_FULFILLED, self::STATUS_CANCELLED], true);
    }
}
