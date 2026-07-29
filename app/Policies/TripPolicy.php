<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'gov_supervisor', 'port_supervisor', 'stat_employee'], true);
    }

    public function create(User $user): bool
    {
        return $user->role->code === 'super_admin';
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->role->code === 'super_admin';
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->role->code === 'super_admin';
    }

    public function startCounting(User $user, Trip $trip): bool
    {
        $employee = $user->employee()->first();

        return $user->role->code === 'stat_employee'
            && $employee !== null
            && in_array($trip->status, ['arrived', 'waiting_employee'], true)
            && ($trip->assigned_employee_id === null || $trip->assigned_employee_id === $employee->id)
            && $employee->assignments()->whereDate('assignment_date', today())->where('port_id', $trip->port_id)->exists();
    }

    public function recordCatch(User $user, Trip $trip): bool
    {
        $employee = $user->employee()->first();

        return $user->role->code === 'stat_employee'
            && $employee !== null
            && $trip->status === 'counting'
            && $trip->assigned_employee_id === $employee->id;
    }

    public function manageAtPort(User $user, Trip $trip): bool
    {
        return match ($user->role->code) {
            'super_admin' => true,
            'gov_supervisor' => $trip->port()->where('governorate_id', $user->governorate_id)->exists(),
            'port_supervisor' => $trip->port_id === $user->port_id,
            default => false,
        };
    }
}
