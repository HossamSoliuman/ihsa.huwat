<?php

namespace App\Actions;

use App\Models\Port;
use App\Models\User;

class BuildHarborWorkspaceAction
{
    public function execute(User $user, ?Port $selectedPort): array
    {
        $ports = Port::query()->visibleTo($user)->with('governorate.region')->orderBy('name')->get();
        $harbor = $selectedPort ?? $ports->first();

        if ($harbor !== null) {
            $harbor->load([
                'governorate.region',
                'capacities',
                'boats' => fn ($query) => $query->orderBy('boat_type')->orderBy('name'),
                'harborWorkers' => fn ($query) => $query->latest(),
                'harborLicenses' => fn ($query) => $query->latest(),
                'harborViolations' => fn ($query) => $query->with('boat')->latest('violation_date'),
            ]);
        }

        $boatTypes = collect([
            'large' => ['label' => 'قوارب كبيرة'],
            'small' => ['label' => 'قوارب صغيرة'],
            'recreational' => ['label' => 'قوارب نزهة'],
        ])->map(function (array $type, string $key) use ($harbor): array {
            $capacityRecord = $harbor?->capacities->firstWhere('boat_type', $key);
            $boats = $harbor?->boats->where('boat_type', $key) ?? collect();
            $capacity = (int) ($capacityRecord?->capacity ?? 0);
            $occupied = $boats->where('harbor_status', 'occupied')->count();
            $disabled = $boats->where('harbor_status', 'disabled')->count();

            return [...$type, 'capacity' => $capacity, 'occupied' => $occupied, 'disabled' => $disabled,
                'available' => max(0, $capacity - $occupied), 'percent' => $capacity > 0 ? min(100, round(($occupied / $capacity) * 100, 1)) : 0,
                'status' => $capacityRecord?->status === 'stopped' ? 'stopped' : ($capacity > 0 && $occupied >= $capacity ? 'full' : 'available')];
        });

        return compact('ports', 'harbor', 'boatTypes');
    }
}
