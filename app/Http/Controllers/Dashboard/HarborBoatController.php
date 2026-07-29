<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteHarborRecordRequest;
use App\Http\Requests\StoreHarborBoatRequest;
use App\Models\Boat;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;

class HarborBoatController extends Controller
{
    public function store(StoreHarborBoatRequest $request, Port $port): RedirectResponse
    {
        $port->boats()->create($request->validated());

        return back()->with('status', 'تمت إضافة القارب.');
    }

    public function update(StoreHarborBoatRequest $request, Port $port, Boat $boat): RedirectResponse
    {
        $this->ensureBelongsToPort($boat, $port);
        $boat->update($request->validated());

        return back()->with('status', 'تم تحديث بيانات القارب.');
    }

    public function destroy(DeleteHarborRecordRequest $request, Port $port, Boat $boat, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $this->ensureBelongsToPort($boat, $port);
        $action->execute($boat, ['trips' => 'boat_id', 'harbor_violations' => 'boat_id'], 'القارب');

        return back()->with('status', 'تم حذف القارب.');
    }

    private function ensureBelongsToPort(Boat $boat, Port $port): void
    {
        abort_unless($boat->home_port_id === $port->id, 404);
    }
}
