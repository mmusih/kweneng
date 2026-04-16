<?php

namespace App\Models;

use App\Support\UserRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
    ];

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRoles::ADMIN;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRoles::TEACHER;
    }

    public function isHeadmaster(): bool
    {
        return $this->role === UserRoles::HEADMASTER;
    }

    public function isAcademicStaff(): bool
    {
        return in_array($this->role, UserRoles::academicStaff(), true);
    }

    public function isLibrarian(): bool
    {
        return $this->role === UserRoles::LIBRARIAN;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function parent()
    {
        return $this->hasOne(ParentModel::class);
    }

    public function accountsOfficer()
    {
        return $this->hasOne(AccountsOfficer::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
