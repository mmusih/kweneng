<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkParentRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'student_id',
        'homework_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }
}
