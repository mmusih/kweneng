<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniInterest extends Model
{
    use HasFactory;

    protected $table = 'alumni_interests';

    protected $fillable = [
        'full_name',
        'email',
        'graduation_year',
        'phone',
        'processed',
    ];

    protected $casts = [
        'processed' => 'boolean',
    ];
}
