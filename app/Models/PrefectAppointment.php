<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrefectAppointment extends Model
{
    public const CERTIFICATE_CITATION_PREFECT = 'In recognition of your commitment, discipline, and willingness to serve others as a Prefect. Your leadership and positive contribution to the school community are highly commended.';

    public const CERTIFICATE_CITATION_DEPUTY_HEAD_GIRL = 'In recognition of exemplary leadership, dedication, responsibility, and outstanding service to the school community in the capacity of Deputy Head Girl.';

    public const CERTIFICATE_CITATION_DEPUTY_HEAD_BOY = 'In recognition of exemplary leadership, dedication, responsibility, and outstanding service to the school community in the capacity of Deputy Head Boy.';

    public const CERTIFICATE_CITATION_HEAD_GIRL = 'In recognition of exceptional leadership, integrity, dedication, and outstanding service to the school community in the capacity of Head Girl. Your commitment to excellence and positive influence are sincerely commended.';

    public const CERTIFICATE_CITATION_HEAD_BOY = 'In recognition of exceptional leadership, integrity, dedication, and outstanding service to the school community in the capacity of Head Boy. Your commitment to excellence and positive influence are sincerely commended.';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'student_id', 'academic_year_id', 'title', 'duties', 'appointed_on', 'service_ends_on',
        'status', 'notes', 'class_name_snapshot', 'certificate_reference', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'appointed_on' => 'date',
        'service_ends_on' => 'date',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REVOKED => 'Revoked',
        ];
    }

    public function certificateCitation(): string
    {
        $title = strtolower(trim((string) preg_replace('/\s+/', ' ', $this->title)));

        return match ($title) {
            'deputy head girl' => self::CERTIFICATE_CITATION_DEPUTY_HEAD_GIRL,
            'deputy head boy' => self::CERTIFICATE_CITATION_DEPUTY_HEAD_BOY,
            'head girl' => self::CERTIFICATE_CITATION_HEAD_GIRL,
            'head boy' => self::CERTIFICATE_CITATION_HEAD_BOY,
            default => self::CERTIFICATE_CITATION_PREFECT,
        };
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
