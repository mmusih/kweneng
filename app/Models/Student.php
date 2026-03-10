<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'admission_no',
        'gender',
        'date_of_birth',
        'current_class_id',
        'photo',
        'results_access',
        'fees_blocked',
    ];

    protected $casts = [
        'results_access' => 'boolean',
        'fees_blocked' => 'boolean',
        'date_of_birth' => 'date',
    ];

    protected $dates = [
        'date_of_birth',
        'deleted_at', // Add this for soft deletes
    ];

    /**
     * Get the user record associated with the student.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the current class associated with the student.
     */
    public function currentClass()
    {
        return $this->belongsTo(ClassModel::class, 'current_class_id');
    }

    /**
     * Get the parents associated with the student.
     */
    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    /**
     * Get class history records.
     */
    public function classHistory()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    /**
     * Get the marks for the student.
     */
    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    /**
     * Get the student subjects.
     */
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
}
