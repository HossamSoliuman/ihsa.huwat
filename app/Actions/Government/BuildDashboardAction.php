<?php

namespace App\Actions\Government;

use App\Models\Employee;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Season;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class BuildDashboardAction
{
    /**
     * @return array{
     *     kpi: array<string, int|float>,
     *     regionProduction: SupportCollection,
     *     recentSeasons: Collection<int, Season>,
     *     generatedAt: Carbon
     * }
     */
    public function handle(): array
    {
        $seasonSummary = Season::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as active', [Season::STATUS_ACTIVE])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN licenses_count ELSE 0 END), 0) as active_licenses', [Season::STATUS_ACTIVE])
            ->first();

        return [
            'kpi' => [
                'trips' => Trip::query()->count(),
                'active_employees' => Employee::query()->where('status', 'active')->count(),
                'yearly_production' => (float) Trip::query()
                    ->whereYear('actual_arrival', today()->year)
                    ->whereIn('status', Trip::VERIFIED_STATUSES)
                    ->sum('verified_weight'),
                'active_seasons' => (int) $seasonSummary->active,
                'active_ports' => Port::query()->where('is_active', true)->count(),
                'active_season_licenses' => (int) $seasonSummary->active_licenses,
                'today_trips' => Trip::query()->whereDate('actual_arrival', today())->count(),
                'pending_reviews' => TripDiscrepancy::query()
                    ->where('review_status', '!=', 'approved')
                    ->distinct('trip_id')
                    ->count('trip_id'),
            ],
            'regionProduction' => $this->regionProduction(),
            'recentSeasons' => Season::query()
                ->select(['id', 'region_id', 'name', 'status', 'start_date', 'end_date', 'licenses_count'])
                ->with('region:id,name')
                ->latest()
                ->limit(5)
                ->get(),
            'generatedAt' => now()->locale('ar'),
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
}
