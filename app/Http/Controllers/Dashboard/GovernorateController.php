<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreGovernorateRequest;
use App\Models\Governorate;
use Illuminate\Http\RedirectResponse;

class GovernorateController extends Controller
{
    public function store(StoreGovernorateRequest $request): RedirectResponse
    {
        Governorate::query()->create($request->validated());

        return $this->redirect()->with('status', 'تمت إضافة المحافظة.');
    }

    public function destroy(DeleteMasterDataRequest $request, Governorate $governorate, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($governorate, ['ports' => 'governorate_id', 'users' => 'governorate_id'], 'المحافظة');

        return $this->redirect()->with('status', 'تم حذف المحافظة.');
    }

    private function redirect(): RedirectResponse
    {
        return to_route('dashboard.master-data.index', ['section' => 'governorates']);
    }
}
