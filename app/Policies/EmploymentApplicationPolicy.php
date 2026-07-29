<?php

namespace App\Policies;

use App\Models\EmploymentApplication;
use App\Models\User;

class EmploymentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesRecruitment($user);
    }

    public function view(User $user, EmploymentApplication $employmentApplication): bool
    {
        return $this->managesRecruitment($user);
    }

    public function update(User $user, EmploymentApplication $employmentApplication): bool
    {
        return $this->managesRecruitment($user);
    }

    public function provision(User $user, EmploymentApplication $employmentApplication): bool
    {
        return $this->managesRecruitment($user);
    }

    private function managesRecruitment(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager'], true);
    }
}
