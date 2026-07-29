<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;

class TripDiscrepancyPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role->code, ['super_admin', 'gov_supervisor', 'port_supervisor', 'quality_supervisor'], true);
    }

    public function approve(User $user, TripDiscrepancy $tripDiscrepancy): bool
    {
        if (in_array($user->role->code, ['super_admin', 'quality_supervisor'], true)) {
            return true;
        }

        return match ($user->role->code) {
            'gov_supervisor' => Trip::query()->whereKey($tripDiscrepancy->trip_id)
                ->whereHas('port', fn ($query) => $query->where('governorate_id', $user->governorate_id))->exists(),
            'port_supervisor' => Trip::query()->whereKey($tripDiscrepancy->trip_id)->where('port_id', $user->port_id)->exists(),
            default => false,
        };
    }
}
