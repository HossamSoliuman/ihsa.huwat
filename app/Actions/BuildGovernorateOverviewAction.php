<?php

namespace App\Actions;

use App\Models\Governorate;
use App\Models\Port;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildGovernorateOverviewAction
{
    public function execute(User $user, array $filters): array
    {
        $governorates = Governorate::query()->with('region:id,name')
            ->when($user->role->code === 'region_manager', fn (Builder $query) => $query->where('region_id', $user->region_id))
            ->when($user->role->code === 'gov_supervisor', fn (Builder $query) => $query->whereKey($user->governorate_id))
            ->orderBy('name')->get();
        $governorate = $governorates->firstWhere('id', (int) ($filters['governorate_id'] ?? 0)) ?? $governorates->first();

        if (! $governorate) {
            return compact('governorates', 'governorate') + $this->emptyState();
        }

        $ports = Port::query()->where('governorate_id', $governorate->id)->with([
            'assignments' => fn ($query) => $query->whereDate('assignment_date', today())->with([
                'employee.user:id,full_name', 'shift:id,name,start_time,end_time',
                'employee.attendance' => fn ($query) => $query->whereDate('attendance_date', today()),
                'employee.assignedTrips' => fn ($query) => $query->whereIn('status', ['waiting_employee', 'counting']),
            ]),
        ])->orderBy('name')->get();
        $portIds = $ports->modelKeys();
        $todayTrips = Trip::query()->whereIn('port_id', $portIds)->whereDate('actual_arrival', today())->get();
        $shiftRows = $ports->flatMap(fn (Port $port) => $port->assignments->map(function ($assignment) use ($port): array {
            $attendance = $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id);

            return ['assignment' => $assignment, 'port' => $port, 'attendance' => $attendance, 'active_trips' => $assignment->employee->assignedTrips->count()];
        }))->sortBy(fn (array $row) => $row['assignment']->shift->start_time)->values();

        $kpi = [
            'ports' => $ports->count(), 'employees' => $shiftRows->pluck('assignment.employee_id')->unique()->count(),
            'present' => $shiftRows->filter(fn (array $row) => in_array($row['attendance']?->status, ['present', 'late'], true))->count(),
            'expected' => Trip::query()->whereIn('port_id', $portIds)->where('status', 'expected')->count(),
            'arrived' => Trip::query()->whereIn('port_id', $portIds)->whereIn('status', ['arrived', 'waiting_employee'])->count(),
            'counting' => Trip::query()->whereIn('port_id', $portIds)->where('status', 'counting')->count(),
            'approved' => $todayTrips->whereIn('status', Trip::VERIFIED_STATUSES)->count(),
            'diff_trips' => TripDiscrepancy::query()->whereIn('trip_id', Trip::query()->whereIn('port_id', $portIds)->select('id'))->where('review_status', '!=', 'approved')->distinct()->count('trip_id'),
        ];
        $portRows = $ports->map(fn (Port $port) => [
            'port' => $port,
            'trips' => $todayTrips->where('port_id', $port->id)->count(),
            'weight' => (float) $todayTrips->where('port_id', $port->id)->whereIn('status', Trip::VERIFIED_STATUSES)->sum('verified_weight'),
        ])->sortByDesc('trips')->values();
        $delayedTrips = Trip::query()->with('port:id,name')->whereIn('port_id', $portIds)->whereIn('status', ['arrived', 'waiting_employee'])->where('actual_arrival', '<=', now()->subMinutes(30))->orderBy('actual_arrival')->get();
        $alerts = $ports->flatMap(function (Port $port) use ($shiftRows, $todayTrips): array {
            if (! $port->is_active) {
                return [];
            }
            $absent = $shiftRows->where('port.id', $port->id)->filter(fn (array $row) => $row['attendance']?->status === 'absent')->count();
            $active = $todayTrips->where('port_id', $port->id)->whereIn('status', ['arrived', 'waiting_employee', 'counting'])->count();
            $rows = [];
            if ($absent > 0) {
                $rows[] = ['port' => $port, 'message' => "{$absent} موظف غائب اليوم", 'severity' => 'warning'];
            }
            if ($active >= 3) {
                $rows[] = ['port' => $port, 'message' => "ازدحام: {$active} قوارب نشطة حالياً", 'severity' => 'critical'];
            }

            return $rows;
        })->values();

        return compact('governorates', 'governorate', 'ports', 'portRows', 'shiftRows', 'delayedTrips', 'alerts', 'kpi');
    }

    private function emptyState(): array
    {
        return ['ports' => collect(), 'portRows' => collect(), 'shiftRows' => collect(), 'delayedTrips' => collect(), 'alerts' => collect(), 'kpi' => []];
    }
}
