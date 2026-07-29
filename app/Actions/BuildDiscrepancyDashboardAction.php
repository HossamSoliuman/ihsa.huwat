<?php

namespace App\Actions;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\CatchDetail;
use App\Models\Employee;
use App\Models\FishSpecies;
use App\Models\Port;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BuildDiscrepancyDashboardAction
{
    public function execute(User $user, array $filters): array
    {
        $tripTable = (new Trip)->getTable();
        $discrepancyTable = (new TripDiscrepancy)->getTable();
        $datedTripIds = Trip::query()->visibleTo($user)
            ->whereBetween('actual_arrival', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
            ->when($filters['port_id'] ?? null, fn ($query, $portId) => $query->where('port_id', $portId))
            ->select($tripTable.'.id');
        $discrepancies = TripDiscrepancy::query()->whereIn('trip_id', clone $datedTripIds);

        $stats = (clone $discrepancies)->toBase()->selectRaw(
            "COUNT(DISTINCT trip_id) AS total_trips, SUM(CASE WHEN severity = 'minor' THEN 1 ELSE 0 END) AS minor_count, SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) AS medium_count, SUM(CASE WHEN severity = 'major' THEN 1 ELSE 0 END) AS major_count, AVG(diff_percent) AS average_percent, SUM(ABS(diff_kg)) AS total_kg, SUM(CASE WHEN review_status <> 'approved' THEN 1 ELSE 0 END) AS pending_count"
        )->first();
        $unreportedCount = CatchDetail::query()->whereIn('trip_id', clone $datedTripIds)->where('is_unreported_by_captain', true)->count();

        $topBoats = $this->rankByRelation(clone $datedTripIds, (new Boat)->getTable(), 'boat_id', 'name', 'boat_name');
        $topCaptains = $this->rankByRelation(clone $datedTripIds, (new Captain)->getTable(), 'captain_id', 'full_name', 'captain_name');

        $catchTable = (new CatchDetail)->getTable();
        $speciesTable = (new FishSpecies)->getTable();
        $topSpecies = CatchDetail::query()->join($speciesTable, $speciesTable.'.id', '=', $catchTable.'.species_id')
            ->whereIn($catchTable.'.trip_id', clone $datedTripIds)
            ->select($speciesTable.'.name_ar')->selectRaw("SUM(ABS({$catchTable}.verified_kg - {$catchTable}.captain_reported_kg)) AS diff_kg")
            ->groupBy($speciesTable.'.id', $speciesTable.'.name_ar')->havingRaw('diff_kg > 0')->orderByDesc('diff_kg')->limit(5)->get();

        $topReasons = (clone $discrepancies)->toBase()->selectRaw("COALESCE(NULLIF(reason, ''), 'غير محدد') AS reason, COUNT(*) AS total")
            ->groupBy('reason')->orderByDesc('total')->limit(6)->get();
        $byPort = $this->rankTripsByPort(clone $datedTripIds);
        $byEmployee = $this->rankTripsByEmployee(clone $datedTripIds);

        $pendingTripIds = Trip::query()->visibleTo($user)
            ->when($filters['port_id'] ?? null, fn ($query, $portId) => $query->where('port_id', $portId))->select($tripTable.'.id');
        $pendingDiscrepancies = TripDiscrepancy::query()->with(['trip.port', 'trip.boat'])
            ->whereIn('trip_id', $pendingTripIds)->where('review_status', '<>', 'approved')
            ->orderByDesc('diff_percent')->limit(100)->get();
        $ports = Port::query()->visibleTo($user)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return compact('filters', 'stats', 'unreportedCount', 'topBoats', 'topCaptains', 'topSpecies', 'topReasons', 'byPort', 'byEmployee', 'pendingDiscrepancies', 'ports');
    }

    private function rankByRelation(Builder $tripIds, string $relationTable, string $foreignKey, string $labelColumn, string $alias): Collection
    {
        $tripTable = (new Trip)->getTable();
        $discrepancyTable = (new TripDiscrepancy)->getTable();

        return TripDiscrepancy::query()->join($tripTable, $tripTable.'.id', '=', $discrepancyTable.'.trip_id')
            ->join($relationTable, $relationTable.'.id', '=', $tripTable.'.'.$foreignKey)
            ->whereIn($discrepancyTable.'.trip_id', $tripIds)->select($relationTable.'.'.$labelColumn.' AS '.$alias)
            ->selectRaw("COUNT(DISTINCT {$discrepancyTable}.trip_id) AS trips_count, AVG({$discrepancyTable}.diff_percent) AS average_diff")
            ->groupBy($relationTable.'.id', $relationTable.'.'.$labelColumn)->orderByDesc('trips_count')->limit(5)->get();
    }

    private function rankTripsByPort(Builder $tripIds): Collection
    {
        $tripTable = (new Trip)->getTable();
        $portTable = (new Port)->getTable();
        $discrepancyTable = (new TripDiscrepancy)->getTable();

        return TripDiscrepancy::query()->join($tripTable, $tripTable.'.id', '=', $discrepancyTable.'.trip_id')
            ->join($portTable, $portTable.'.id', '=', $tripTable.'.port_id')->whereIn($discrepancyTable.'.trip_id', $tripIds)
            ->select($portTable.'.name AS port_name')->selectRaw("COUNT(DISTINCT {$discrepancyTable}.trip_id) AS trips_count")
            ->groupBy($portTable.'.id', $portTable.'.name')->orderByDesc('trips_count')->get();
    }

    private function rankTripsByEmployee(Builder $tripIds): Collection
    {
        $tripTable = (new Trip)->getTable();
        $employeeTable = (new Employee)->getTable();
        $userTable = (new User)->getTable();
        $discrepancyTable = (new TripDiscrepancy)->getTable();

        return TripDiscrepancy::query()->join($tripTable, $tripTable.'.id', '=', $discrepancyTable.'.trip_id')
            ->leftJoin($employeeTable, $employeeTable.'.id', '=', $tripTable.'.assigned_employee_id')
            ->leftJoin($userTable, $userTable.'.id', '=', $employeeTable.'.user_id')->whereIn($discrepancyTable.'.trip_id', $tripIds)
            ->select($userTable.'.full_name AS employee_name')->selectRaw("COUNT(DISTINCT {$tripTable}.id) AS trips_count, AVG({$discrepancyTable}.diff_percent) AS average_diff")
            ->groupBy($userTable.'.id', $userTable.'.full_name')->orderByDesc('trips_count')->limit(8)->get();
    }
}
