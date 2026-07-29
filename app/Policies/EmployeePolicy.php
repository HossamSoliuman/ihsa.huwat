<?php

namespace App\Policies;

use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager', 'gov_supervisor'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager'], true);
    }
}
