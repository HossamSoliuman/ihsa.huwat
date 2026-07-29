<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StorePortRequest;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;

class PortController extends Controller
{
    public function store(StorePortRequest $request): RedirectResponse
    {
        Port::query()->create($request->validated());

        return $this->redirect()->with('status', 'تمت إضافة الميناء.');
    }

    public function toggle(DeleteMasterDataRequest $request, Port $port): RedirectResponse
    {
        $port->update(['is_active' => ! $port->is_active]);

        return $this->redirect()->with('status', 'تم تحديث حالة الميناء.');
    }

    public function destroy(DeleteMasterDataRequest $request, Port $port, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($port, [
            'users' => 'port_id', 'employee_assignments' => 'port_id', 'boats' => 'home_port_id',
            'trips' => 'port_id', 'harbor_boat_capacities' => 'port_id', 'harbor_workers' => 'port_id',
            'harbor_licenses' => 'port_id', 'harbor_violations' => 'port_id', 'employment_jobs' => 'port_id',
            'employment_applications' => 'preferred_port_id',
        ], 'الميناء');

        return $this->redirect()->with('status', 'تم حذف الميناء.');
    }

    private function redirect(): RedirectResponse
    {
        return to_route('dashboard.master-data.index', ['section' => 'ports']);
    }
}
