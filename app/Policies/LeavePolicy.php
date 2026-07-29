<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    public function create(User $user): bool
    {
        return $user->role->code === 'employee_portal' && $user->employee()->exists();
    }

    public function update(User $user, Leave $leave): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager'], true);
    }
}
