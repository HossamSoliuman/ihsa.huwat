<?php

namespace App\Http\Controllers;

use App\Actions\Information\BuildPortProfile;
use App\Http\Requests\ViewInformationPortsRequest;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * الموانئ. A read-only desk: the ports themselves are maintained from the reference lists,
 * and this screen only reads what has gathered around each live one — berth capacity, the
 * boats homed there, its workers, its licences and the submissions filed under it.
 */
class InformationPortController extends Controller
{
    public function index(ViewInformationPortsRequest $request): View
    {
        $filters = $request->validated();
        $scope = $request->user()->informationScope();

        /** Only ports the portal still offers: a port stopped with its geography is not live. */
        $ports = $scope->applyPorts(Port::query())
            ->selectable()
            ->with('governorate.region')
            ->withSum('capacities as berth_capacity', 'capacity')
            ->withCount([
                'boats as occupied_boats_count' => fn (Builder $query): Builder => $query->where('harbor_status', 'occupied'),
                'harborWorkers as workers_count',
                'informationSubmissions as submissions_count',
            ])
            ->when($filters['region_id'] ?? null, fn (Builder $query, int $regionId): Builder => $query->whereIn(
                'governorate_id',
                Governorate::query()->where('region_id', $regionId)->select('id'),
            ))
            ->when($filters['governorate_id'] ?? null, fn (Builder $query, int $governorateId): Builder => $query
                ->where('governorate_id', $governorateId))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('location_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        /** The filters offer only the geography the account holds, so nothing outside it is nameable. */
        return view('information.admin.ports.index', [
            'ports' => $ports,
            'filters' => $filters,
            'regions' => $scope->applyRegions(Region::query())->orderBy('name')->get(['id', 'name']),
            'governorates' => $scope->applyGovernorates(Governorate::query())->orderBy('name')->get(['id', 'region_id', 'name']),
        ]);
    }

    public function show(ViewInformationPortsRequest $request, Port $port, BuildPortProfile $buildPortProfile): View
    {
        return view('information.admin.ports.show', $buildPortProfile->execute($port));
    }
}
