<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;

class EmployeeContractPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager', 'finance_officer'], true);
    }

    public function view(User $user, EmployeeContract $employeeContract): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, Employee $employee): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager'], true);
    }

    public function update(User $user, EmployeeContract $employeeContract): bool
    {
        return $this->create($user, $employeeContract->employee);
    }
}
