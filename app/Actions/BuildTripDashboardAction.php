<?php

namespace App\Actions;

use App\Models\Port;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuildTripDashboardAction
{
    public function execute(User $user, array $filters): array
    {
        $query = Trip::query()
            ->visibleTo($user)
            ->whereBetween(
                DB::raw('COALESCE(actual_arrival, expected_arrival)'),
                [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'],
            )
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['port_id'] ?? null, fn ($query, $portId) => $query->where('port_id', $portId));

        $stats = (clone $query)->selectRaw(
            "COUNT(*) AS total, SUM(CASE WHEN status = 'expected' THEN 1 ELSE 0 END) AS expected_count, SUM(CASE WHEN status IN ('arrived','waiting_employee') THEN 1 ELSE 0 END) AS arrived_count, SUM(CASE WHEN status = 'waiting_employee' THEN 1 ELSE 0 END) AS waiting_count, SUM(CASE WHEN status = 'counting' THEN 1 ELSE 0 END) AS counting_count, SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) AS pending_count, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count, SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed_count"
        )->firstOrFail();

        $countDurations = (clone $query)
            ->whereNotNull('counting_started_at')
            ->whereNotNull('counting_ended_at')
            ->get(['counting_started_at', 'counting_ended_at']);
        $stats->average_count_minutes = $countDurations->avg(fn (Trip $trip) => $trip->counting_started_at->diffInMinutes($trip->counting_ended_at));

        $trips = $query->with(['port', 'boat', 'captain', 'assignedEmployee.user'])
            ->orderByRaw('COALESCE(actual_arrival, expected_arrival) DESC')
            ->paginate(50)
            ->withQueryString();

        $ports = Port::query()->where('is_active', true)
            ->when($user->role->code === 'gov_supervisor', fn ($query) => $query->where('governorate_id', $user->governorate_id))
            ->when($user->role->code === 'port_supervisor', fn ($query) => $query->whereKey($user->port_id))
            ->orderBy('name')->get();

        return compact('trips', 'stats', 'ports', 'filters');
    }
}
