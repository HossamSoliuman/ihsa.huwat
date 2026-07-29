<?php

namespace App\Policies;

use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'gov_supervisor', 'port_supervisor'], true);
    }
}
