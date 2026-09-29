<?php

namespace App\Support;

use App\Models\StaffProfile;
use App\Models\User;

class StaffWorkflows
{
    public static function staff(User $user): ?StaffProfile
    {
        return StaffProfile::where('user_id', $user->id)->where('status', 'active')->first();
    }

    public static function finance(User $user): bool
    {
        return $user->isActive() && in_array($user->role, ['admin', 'accounts_officer'], true);
    }

    public static function approvesSpending(User $user): bool
    {
        return $user->isActive() && in_array($user->role, ['admin', 'headmaster'], true);
    }

    public static function receivesGoods(User $user): bool
    {
        return $user->isActive() && in_array($user->role, ['admin', 'office', 'inventory'], true);
    }
}
