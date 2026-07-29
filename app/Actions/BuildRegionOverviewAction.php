<?php

namespace App\Actions;

use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildRegionOverviewAction
{
    public function execute(User $user, array $filters): array
    {
        $regions = Region::query()
            ->when($user->role->code === 'region_manager', fn (Builder $query) => $query->whereKey($user->region_id))
            ->orderBy('name')->get(['id', 'name']);
        $region = $regions->firstWhere('id', (int) ($filters['region_id'] ?? 0)) ?? $regions->first();

        if (! $region) {
            return compact('regions', 'region') + $this->emptyState();
        }

        $governorates = Governorate::query()->where('region_id', $region->id)->orderBy('name')->get(['id', 'name']);
        $ports = Port::query()->whereIn('governorate_id', $governorates->modelKeys())->with([
            'governorate:id,name,region_id',
            'assignments' => fn ($query) => $query->whereDate('assignment_date', today())->with([
                'employee.user:id,full_name',
                'employee.attendance' => fn ($query) => $query->whereDate('attendance_date', today()),
            ]),
        ])->withCount(['trips as active_trips_count' => fn (Builder $query) => $query->whereIn('status', ['arrived', 'waiting_employee', 'counting'])])
            ->orderBy('name')->get();
        $portIds = $ports->modelKeys();

        $portRows = $ports->map(function (Port $port): array {
            $present = $port->assignments->filter(fn ($assignment) => in_array(
                $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id)?->status,
                ['present', 'late'], true,
            ))->count();
            $status = ! $port->is_active ? 'inactive' : ($present === 0 ? 'uncovered' : ($port->active_trips_count > $present * 2 ? 'high_load' : 'covered'));

            return ['port' => $port, 'present' => $present, 'active_trips' => $port->active_trips_count, 'status' => $status];
        });

        $todayTrips = Trip::query()->whereIn('port_id', $portIds)->whereDate('actual_arrival', today());
        $kpi = [
            'governorates' => $governorates->count(),
            'ports' => $ports->count(),
            'active_employees' => $portRows->sum('present'),
            'absent_employees' => $ports->flatMap->assignments->filter(fn ($assignment) => $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id)?->status === 'absent')->count(),
            'trips_today' => (clone $todayTrips)->count(),
            'approved_catch' => (float) (clone $todayTrips)->whereIn('status', Trip::VERIFIED_STATUSES)->sum('verified_weight'),
            'diff_trips' => TripDiscrepancy::query()->whereIn('trip_id', Trip::query()->whereIn('port_id', $portIds)->select('id'))->where('review_status', '!=', 'approved')->distinct()->count('trip_id'),
            'needs_support' => $portRows->whereIn('status', ['uncovered', 'high_load'])->count(),
        ];

        $thirtyDayTrips = Trip::query()->with('assignedEmployee.user:id,full_name')->whereIn('port_id', $portIds)->whereIn('status', Trip::VERIFIED_STATUSES)->where('actual_arrival', '>=', now()->subDays(30))->get(['id', 'port_id', 'verified_weight', 'assigned_employee_id']);
        $governorateRows = $governorates->map(function (Governorate $governorate) use ($ports, $portRows, $todayTrips, $thirtyDayTrips): array {
            $ids = $ports->where('governorate_id', $governorate->id)->modelKeys();
            $historical = $thirtyDayTrips->whereIn('port_id', $ids);
            $today = (clone $todayTrips)->whereIn('port_id', $ids)->get();
            $todayIds = $today->modelKeys();

            return [
                'governorate' => $governorate,
                'ports' => count($ids),
                'trips_30_days' => $historical->count(),
                'weight_30_days' => (float) $historical->sum('verified_weight'),
                'trips_today' => $today->count(),
                'average_difference' => (float) TripDiscrepancy::query()->whereIn('trip_id', $todayIds)->avg('diff_percent'),
                'covered' => $portRows->filter(fn (array $row) => in_array($row['port']->id, $ids, true) && $row['status'] === 'covered')->count(),
            ];
        })->sortByDesc('weight_30_days')->values();

        $staffDistribution = $ports->map(fn (Port $port) => ['port' => $port, 'employees' => $port->assignments->pluck('employee_id')->unique()->count()])->sortByDesc('employees')->values();
        $busiestPorts = $portRows->sortByDesc('active_trips')->take(5)->values();
        $topEmployees = $thirtyDayTrips->whereNotNull('assigned_employee_id')->groupBy('assigned_employee_id')->map(function (Collection $trips): array {
            $employee = $trips->first()->assignedEmployee;

            return ['employee' => $employee, 'trips' => $trips->count(), 'weight' => (float) $trips->sum('verified_weight')];
        })->filter(fn (array $row) => $row['employee'] !== null)->sortByDesc('trips')->take(5)->values();

        return compact('regions', 'region', 'governorates', 'portRows', 'governorateRows', 'staffDistribution', 'busiestPorts', 'topEmployees', 'kpi');
    }

    private function emptyState(): array
    {
        return ['governorates' => collect(), 'portRows' => collect(), 'governorateRows' => collect(), 'staffDistribution' => collect(), 'busiestPorts' => collect(), 'topEmployees' => collect(), 'kpi' => []];
    }
}
