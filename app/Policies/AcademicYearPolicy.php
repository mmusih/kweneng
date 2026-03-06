<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Auth\Access\HandlesAuthorization;

class AcademicYearPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view list
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return true; // All authenticated users can view individual years
    }

    public function create(User $user): bool
    {
        // Only admins can create academic years
        return $user->role === 'admin';
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        // Cannot edit locked or closed years
        if ($academicYear->isLocked() || $academicYear->isClosed()) {
            return false;
        }
        
        return $user->role === 'admin';
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        // Cannot delete locked or closed years
        if ($academicYear->isLocked() || $academicYear->isClosed()) {
            return false;
        }
        
        // Cannot delete years with associated data
        if ($academicYear->classes()->count() > 0 || 
            $academicYear->terms()->count() > 0 ||
            $academicYear->studentClassHistories()->count() > 0) {
            return false;
        }
        
        return $user->role === 'admin';
    }

    public function lock(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function close(User $user): bool
    {
        return $user->role === 'admin';
    }
}
