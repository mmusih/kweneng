<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentStudent extends Model
{
    protected $table = 'parent_student';
    
    protected $fillable = [
        'parent_id',
        'student_id',
        'relationship',
    ];
    
    public $timestamps = true;
}
