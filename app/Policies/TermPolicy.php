<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Term;
use Illuminate\Auth\Access\HandlesAuthorization;

class TermPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Term $term): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Term $term): bool
    {
        // Cannot edit locked or finalized terms
        if ($term->isLocked() || $term->isFinalized()) {
            return false;
        }
        
        // Cannot edit terms in locked/closed academic years
        if ($term->academicYear && ($term->academicYear->isLocked() || $term->academicYear->isClosed())) {
            return false;
        }
        
        return $user->role === 'admin';
    }

    public function delete(User $user, Term $term): bool
    {
        // Cannot delete locked or finalized terms
        if ($term->isLocked() || $term->isFinalized()) {
            return false;
        }
        
        // Cannot delete terms in locked/closed academic years
        if ($term->academicYear && ($term->academicYear->isLocked() || $term->academicYear->isClosed())) {
            return false;
        }
        
        return $user->role === 'admin';
    }

    public function finalize(User $user, Term $term): bool
    {
        return $user->role === 'admin' && !$term->isLocked();
    }

    public function lock(User $user, Term $term): bool
    {
        return $user->role === 'admin';
    }
}
