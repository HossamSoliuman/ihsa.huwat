<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreCaptainRequest;
use App\Models\Captain;
use Illuminate\Http\RedirectResponse;

class CaptainController extends Controller
{
    public function store(StoreCaptainRequest $request): RedirectResponse
    {
        Captain::query()->create($request->validated());

        return $this->redirect()->with('status', 'تمت إضافة الكابتن.');
    }

    public function destroy(DeleteMasterDataRequest $request, Captain $captain, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($captain, ['trips' => 'captain_id'], 'الكابتن');

        return $this->redirect()->with('status', 'تم حذف الكابتن.');
    }

    private function redirect(): RedirectResponse
    {
        return to_route('dashboard.master-data.index', ['section' => 'captains']);
    }
}
