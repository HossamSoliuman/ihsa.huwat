<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'finance_officer', 'hr_manager'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $this->viewAny($user);
    }

    public function pay(User $user, Payroll $payroll): bool
    {
        return $this->viewAny($user);
    }
}
