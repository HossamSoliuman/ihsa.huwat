<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreRegionRequest;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;

class RegionController extends Controller
{
    public function store(StoreRegionRequest $request): RedirectResponse
    {
        Region::query()->create($request->validated());

        return $this->redirect()->with('status', 'تمت إضافة المنطقة.');
    }

    public function destroy(DeleteMasterDataRequest $request, Region $region, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($region, ['governorates' => 'region_id', 'users' => 'region_id', 'seasons' => 'region_id'], 'المنطقة');

        return $this->redirect()->with('status', 'تم حذف المنطقة.');
    }

    private function redirect(): RedirectResponse
    {
        return to_route('dashboard.master-data.index', ['section' => 'regions']);
    }
}
