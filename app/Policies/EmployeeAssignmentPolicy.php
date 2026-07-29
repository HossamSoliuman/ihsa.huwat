<?php

namespace App\Policies;

use App\Models\EmployeeAssignment;
use App\Models\User;

class EmployeeAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'region_manager', 'gov_supervisor', 'hr_manager', 'port_supervisor'], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmployeeAssignment $employeeAssignment): bool
    {
        return match ($user->role->code) {
            'super_admin', 'hr_manager' => true,
            'region_manager' => $employeeAssignment->port()->whereHas('governorate', fn ($query) => $query->where('region_id', $user->region_id))->exists(),
            'gov_supervisor' => $employeeAssignment->port()->where('governorate_id', $user->governorate_id)->exists(),
            'port_supervisor' => $user->port_id === $employeeAssignment->port_id,
            default => false,
        };
    }
}
