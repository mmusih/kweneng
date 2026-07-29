<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'phone',
        'address',
    ];

    /**
     * Get the user record associated with the parent.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the students associated with the parent.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }
    public function absenceNotices()
    {
        return $this->hasMany(ParentAbsenceNotice::class, 'parent_id');
    }

    public function homeworkReads()
    {
        return $this->hasMany(HomeworkParentRead::class, 'parent_id');
    }
}
