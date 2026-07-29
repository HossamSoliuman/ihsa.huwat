<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViewMasterDataRequest;
use App\Models\Boat;
use App\Models\Captain;
use App\Models\FishSpecies;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Contracts\View\View;

class MasterDataController extends Controller
{
    public function __invoke(ViewMasterDataRequest $request): View
    {
        $section = $request->validated('section', 'regions');
        $data = match ($section) {
            'governorates' => [
                'records' => Governorate::query()->with('region')->withCount('ports')->orderBy('name')->get(),
                'regions' => Region::query()->orderBy('name')->get(),
            ],
            'ports' => [
                'records' => Port::query()->with('governorate.region')->orderBy('name')->get(),
                'governorates' => Governorate::query()->with('region')->orderBy('name')->get(),
            ],
            'boats' => [
                'records' => Boat::query()->with('homePort')->withCount('trips')->orderBy('name')->get(),
                'ports' => Port::query()->where('is_active', true)->orderBy('name')->get(),
            ],
            'captains' => ['records' => Captain::query()->withCount('trips')->orderBy('full_name')->get()],
            'species' => ['records' => FishSpecies::query()->withCount('catchDetails')->orderBy('name_ar')->get()],
            default => ['records' => Region::query()->withCount('governorates')->orderBy('name')->get()],
        };

        return view('dashboard.master-data.index', ['section' => $section, ...$data]);
    }
}
