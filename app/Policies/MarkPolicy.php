<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Mark;
use Illuminate\Auth\Access\HandlesAuthorization;

class MarkPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Admins and teachers can view marks
        return in_array($user->role, ['admin', 'teacher']);
    }

    public function view(User $user, Mark $mark): bool
    {
        // Admins can view any marks
        if ($user->role === 'admin') {
            return true;
        }
        
        // Teachers can view their own marks
        if ($user->role === 'teacher') {
            return $user->teacher->id === $mark->teacher_id;
        }
        
        return false;
    }

    public function create(User $user): bool
    {
        // Only teachers and admins can create marks
        return in_array($user->role, ['admin', 'teacher']);
    }

    public function update(User $user, Mark $mark): bool
    {
        // Admins can update any marks
        if ($user->role === 'admin') {
            return true;
        }
        
        // Teachers can update their own marks if term is active
        if ($user->role === 'teacher') {
            return $user->teacher->id === $mark->teacher_id 
                && $mark->term->status === 'active';
        }
        
        return false;
    }

    public function delete(User $user, Mark $mark): bool
    {
        // Only admins can delete marks
        return $user->role === 'admin';
    }

    public function override(User $user): bool
    {
        // Only admins can override marks
        return $user->role === 'admin';
    }
}
