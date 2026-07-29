<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreBoatRequest;
use App\Models\Boat;
use Illuminate\Http\RedirectResponse;

class BoatController extends Controller
{
    public function store(StoreBoatRequest $request): RedirectResponse
    {
        Boat::query()->create($request->validated());

        return $this->redirect()->with('status', 'تمت إضافة القارب.');
    }

    public function destroy(DeleteMasterDataRequest $request, Boat $boat, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($boat, ['trips' => 'boat_id', 'harbor_violations' => 'boat_id'], 'القارب');

        return $this->redirect()->with('status', 'تم حذف القارب.');
    }

    private function redirect(): RedirectResponse
    {
        return to_route('dashboard.master-data.index', ['section' => 'boats']);
    }
}
