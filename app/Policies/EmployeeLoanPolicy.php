<?php

namespace App\Policies;

use App\Models\EmployeeLoan;
use App\Models\User;

class EmployeeLoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['super_admin', 'hr_manager', 'finance_officer']);
    }

    public function view(User $user, EmployeeLoan $employeeLoan): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function approve(User $user, EmployeeLoan $employeeLoan): bool
    {
        return $this->hasRole($user, ['super_admin', 'finance_officer']);
    }

    /** @param list<string> $roles */
    private function hasRole(User $user, array $roles): bool
    {
        return in_array($user->role->code, $roles, true);
    }
}
