<?php

namespace App\Policies;

use App\Models\EmploymentJob;
use App\Models\User;

class EmploymentJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->managesRecruitment($user);
    }

    public function view(User $user, EmploymentJob $employmentJob): bool
    {
        return $this->managesRecruitment($user);
    }

    public function create(User $user): bool
    {
        return $this->managesRecruitment($user);
    }

    public function update(User $user, EmploymentJob $employmentJob): bool
    {
        return $this->managesRecruitment($user);
    }

    public function transition(User $user, EmploymentJob $employmentJob): bool
    {
        return $this->managesRecruitment($user);
    }

    public function delete(User $user, EmploymentJob $employmentJob): bool
    {
        return false;
    }

    private function managesRecruitment(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'hr_manager'], true);
    }
}
