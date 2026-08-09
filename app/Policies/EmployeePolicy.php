<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['super_admin', 'hr_manager', 'finance_officer']);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, ['super_admin', 'hr_manager']);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->create($user);
    }

    public function viewSalary(User $user, Employee $employee): bool
    {
        return $this->hasRole($user, ['super_admin', 'hr_manager', 'finance_officer']);
    }

    public function updateSalary(User $user, Employee $employee): bool
    {
        return $this->hasRole($user, ['super_admin', 'hr_manager']);
    }

    public function viewPayslip(User $user, Employee $employee): bool
    {
        return $this->viewSalary($user, $employee) || $employee->user_id === $user->id;
    }

    /** @param list<string> $roles */
    private function hasRole(User $user, array $roles): bool
    {
        return in_array($user->role->code, $roles, true);
    }
}
