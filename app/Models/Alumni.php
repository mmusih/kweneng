<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    // Specify the exact table name
    protected $table = 'alumni';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'graduation_year',
        'current_occupation',
        'company',
        'location',
        'bio',
        'linkedin_url',
        'is_published',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'is_published' => 'boolean',
    ];
}
