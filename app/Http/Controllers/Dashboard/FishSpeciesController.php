<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteMasterDataRequest;
use App\Http\Requests\StoreFishSpeciesRequest;
use App\Models\FishSpecies;
use Illuminate\Http\RedirectResponse;

class FishSpeciesController extends Controller
{
    public function store(StoreFishSpeciesRequest $request): RedirectResponse
    {
        FishSpecies::query()->create($request->validated());

        return $this->redirect()->with('status', 'تمت إضافة نوع السمك.');
    }

    public function destroy(DeleteMasterDataRequest $request, FishSpecies $species, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($species, ['catch_details' => 'species_id'], 'نوع السمك');

        return $this->redirect()->with('status', 'تم حذف نوع السمك.');
    }

    private function redirect(): RedirectResponse
    {
        return to_route('dashboard.master-data.index', ['section' => 'species']);
    }
}
