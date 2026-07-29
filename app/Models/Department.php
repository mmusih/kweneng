<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(DepartmentUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot(['id', 'academic_year_id', 'role_in_department'])
            ->withTimestamps();
    }

    public function hods(): BelongsToMany
    {
        return $this->users()->wherePivot('role_in_department', 'hod');
    }

    public function teachers(): BelongsToMany
    {
        return $this->users()->wherePivot('role_in_department', 'teacher');
    }
}
