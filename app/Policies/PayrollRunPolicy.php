<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, ['super_admin', 'hr_manager', 'finance_officer']);
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, ['super_admin', 'finance_officer']);
    }

    public function calculate(User $user, PayrollRun $payrollRun): bool
    {
        return $this->create($user);
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        return $this->create($user);
    }

    public function markPaid(User $user, PayrollRun $payrollRun): bool
    {
        return $this->create($user);
    }

    public function close(User $user, PayrollRun $payrollRun): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        return $this->create($user);
    }

    /** @param list<string> $roles */
    private function hasRole(User $user, array $roles): bool
    {
        return in_array($user->role->code, $roles, true);
    }
}
