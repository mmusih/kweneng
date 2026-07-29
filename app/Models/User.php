<?php

namespace App\Models;

use App\Support\UserRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

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
        'email_verified_at'    => 'datetime',
        'password'             => 'hashed',
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

    public function isOffice(): bool
    {
        return $this->role === UserRoles::OFFICE;
    }

    public function isRegisterOfficer(): bool
    {
        return $this->role === UserRoles::REGISTER_OFFICER;
    }

    public function isInventory(): bool
    {
        return $this->role === UserRoles::INVENTORY;
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


    public function departmentAssignments()
    {
        return $this->hasMany(DepartmentUser::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot(['id', 'academic_year_id', 'role_in_department'])
            ->withTimestamps();
    }

    public function hodDepartments(?int $academicYearId = null)
    {
        return $this->departments()
            ->wherePivot('role_in_department', DepartmentUser::ROLE_HOD)
            ->when($academicYearId, fn ($query) => $query->wherePivot('academic_year_id', $academicYearId));
    }

    public function isDepartmentHod(?int $academicYearId = null): bool
    {
        return $this->departmentAssignments()
            ->where('role_in_department', DepartmentUser::ROLE_HOD)
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where(function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId)
                        ->orWhereNull('academic_year_id');
                });
            })
            ->exists();
    }

    public function hodDepartmentIds(?int $academicYearId = null): array
    {
        return $this->departmentAssignments()
            ->where('role_in_department', DepartmentUser::ROLE_HOD)
            ->when($academicYearId, function ($query) use ($academicYearId) {
                $query->where(function ($q) use ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId)
                        ->orWhereNull('academic_year_id');
                });
            })
            ->pluck('department_id')
            ->unique()
            ->values()
            ->all();
    }
}
