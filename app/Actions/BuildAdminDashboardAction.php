<?php

namespace App\Actions;

use App\Models\Alert;
use App\Models\CatchDetail;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\FishSpecies;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class BuildAdminDashboardAction
{
    /**
     * @return array{kpi: array<string, int|float>, regionProduction: SupportCollection, topSpecies: SupportCollection, alerts: Collection<int, Alert>}
     */
    public function handle(): array
    {
        return [
            'kpi' => $this->keyPerformanceIndicators(),
            'regionProduction' => $this->regionProduction(),
            'topSpecies' => $this->topSpecies(),
            'alerts' => Alert::query()->where('is_resolved', false)->latest()->limit(8)->get(),
        ];
    }

    /** @return array<string, int|float> */
    private function keyPerformanceIndicators(): array
    {
        return [
            'regions' => Region::query()->count(),
            'governorates' => Governorate::query()->count(),
            'ports' => Port::query()->where('is_active', true)->count(),
            'employees' => Employee::query()->where('status', 'active')->count(),
            'boats_today' => Trip::query()->whereDate('actual_arrival', today())->count(),
            'catch_today' => (float) Trip::query()->whereDate('actual_arrival', today())->whereIn('status', Trip::VERIFIED_STATUSES)->sum('verified_weight'),
            'diff_trips' => TripDiscrepancy::query()->where('review_status', '!=', 'approved')->distinct('trip_id')->count('trip_id'),
            'uncovered_ports' => Port::query()->where('is_active', true)->whereNotIn(
                'id',
                EmployeeAssignment::query()->whereDate('assignment_date', today())->select('port_id'),
            )->count(),
        ];
    }

    private function regionProduction(): SupportCollection
    {
        $regions = (new Region)->getTable();
        $governorates = (new Governorate)->getTable();
        $ports = (new Port)->getTable();
        $trips = (new Trip)->getTable();

        return Region::query()
            ->select($regions.'.id', $regions.'.name as region_name')
            ->selectRaw('COALESCE(SUM('.$trips.'.verified_weight), 0) AS total_kg')
            ->leftJoin($governorates, $governorates.'.region_id', '=', $regions.'.id')
            ->leftJoin($ports, $ports.'.governorate_id', '=', $governorates.'.id')
            ->leftJoin($trips, function ($join) use ($ports, $trips): void {
                $join->on($trips.'.port_id', '=', $ports.'.id')
                    ->whereIn($trips.'.status', Trip::VERIFIED_STATUSES)
                    ->where($trips.'.actual_arrival', '>=', now()->subDays(30));
            })
            ->groupBy($regions.'.id', $regions.'.name')
            ->orderByDesc('total_kg')
            ->get();
    }

    private function topSpecies(): SupportCollection
    {
        $species = (new FishSpecies)->getTable();
        $catchDetails = (new CatchDetail)->getTable();
        $trips = (new Trip)->getTable();

        return FishSpecies::query()
            ->select($species.'.id', $species.'.name_ar')
            ->selectRaw('SUM('.$catchDetails.'.verified_kg) AS total_kg')
            ->join($catchDetails, $catchDetails.'.species_id', '=', $species.'.id')
            ->join($trips, $trips.'.id', '=', $catchDetails.'.trip_id')
            ->whereIn($trips.'.status', Trip::VERIFIED_STATUSES)
            ->groupBy($species.'.id', $species.'.name_ar')
            ->orderByDesc('total_kg')
            ->limit(5)
            ->get();
    }
}
