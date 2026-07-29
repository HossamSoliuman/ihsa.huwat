<?php

namespace App\Policies;

use App\Models\Governorate;
use App\Models\Port;
use App\Models\User;

class PortPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'region_manager', 'gov_supervisor', 'port_supervisor'], true);
    }

    public function view(User $user, Port $port): bool
    {
        return match ($user->role->code) {
            'super_admin' => true,
            'region_manager' => Governorate::query()->whereKey($port->governorate_id)->where('region_id', $user->region_id)->exists(),
            'gov_supervisor' => $port->governorate_id === $user->governorate_id,
            'port_supervisor' => $port->id === $user->port_id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'region_manager', 'gov_supervisor'], true);
    }

    public function update(User $user, Port $port): bool
    {
        return $this->view($user, $port);
    }

    public function delete(User $user, Port $port): bool
    {
        return $user->role->code === 'super_admin';
    }
}
