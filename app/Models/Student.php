<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    public const DOCUMENT_BIRTH_CERTIFICATE = 'birth_certificate';
    public const DOCUMENT_ID_NUMBER = 'id_number';
    public const DOCUMENT_PASSPORT = 'passport';

    protected $fillable = [
        'user_id',
        'admission_no',
        'gender',
        'date_of_birth',
        'nationality',
        'identity_document_type',
        'identity_document_number',
        'current_class_id',
        'photo',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_alt_phone',
        'emergency_contact_address',
        'medical_notes',
        'profile_updated_by_parent_at',
        'results_access',
        'fees_blocked',
    ];

    protected $casts = [
        'results_access' => 'boolean',
        'fees_blocked'   => 'boolean',
        'date_of_birth'  => 'date',
        'profile_updated_by_parent_at' => 'datetime',
    ];

    protected $dates = [
        'date_of_birth',
        'deleted_at',
    ];

    public static function identityDocumentTypes(): array
    {
        return [
            self::DOCUMENT_BIRTH_CERTIFICATE => 'Birth Certificate Number',
            self::DOCUMENT_ID_NUMBER => 'ID Number',
            self::DOCUMENT_PASSPORT => 'Passport Number',
        ];
    }

    public function identityDocumentLabel(): string
    {
        return self::identityDocumentTypes()[$this->identity_document_type] ?? 'Identity Document';
    }

    public function identityDisplay(): string
    {
        if (! filled($this->identity_document_type) && ! filled($this->identity_document_number)) {
            return $this->admission_no ? 'Legacy No: ' . $this->admission_no : 'Missing';
        }

        return $this->identityDocumentLabel() . ': ' . ($this->identity_document_number ?: 'Missing');
    }

    public function profileCompletionIssues(): array
    {
        $issues = [];

        if (! filled($this->nationality)) {
            $issues[] = 'Nationality';
        }

        if (! filled($this->identity_document_type) || ! filled($this->identity_document_number)) {
            $issues[] = 'ID / Passport / Birth Certificate';
        }

        if (! filled($this->emergency_contact_name)) {
            $issues[] = 'Emergency contact name';
        }

        if (! filled($this->emergency_contact_relationship)) {
            $issues[] = 'Emergency contact relationship';
        }

        if (! filled($this->emergency_contact_phone)) {
            $issues[] = 'Emergency contact phone';
        }

        return $issues;
    }

    public function isProfileComplete(): bool
    {
        return $this->profileCompletionIssues() === [];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currentClass()
    {
        return $this->belongsTo(ClassModel::class, 'current_class_id');
    }

    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    /**
     * All parent invite codes ever generated for this student.
     */
    public function parentCodes()
    {
        return $this->hasMany(StudentParentCode::class);
    }

    /**
     * Only the current valid (unused + not expired) parent code.
     */
    public function activeParentCode()
    {
        return $this->hasOne(StudentParentCode::class)->valid()->latest();
    }

    public function classHistory()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    public function studentSubjects()
    {
        return $this->hasMany(StudentSubject::class);
    }

    public function headmasterComments()
    {
        return $this->hasMany(HeadmasterComment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function punctualities()
    {
        return $this->hasMany(Punctuality::class);
    }

    public function behaviourRecords()
    {
        return $this->hasMany(BehaviourRecord::class);
    }

    public function termSummaries()
    {
        return $this->hasMany(StudentTermSummary::class);
    }

    public function libraryBorrowings()
    {
        return $this->hasMany(LibraryBorrowing::class);
    }

    public function parentAbsenceNotices()
    {
        return $this->hasMany(ParentAbsenceNotice::class);
    }

    public function feeBalances()
    {
        return $this->hasMany(StudentFeeBalance::class);
    }

    public function latestFeeBalance()
    {
        return $this->hasOne(StudentFeeBalance::class)->latestOfMany();
    }

    public function homeworkMarks()
    {
        return $this->hasMany(HomeworkMark::class);
    }

    public function homeworkParentReads()
    {
        return $this->hasMany(HomeworkParentRead::class);
    }
}
