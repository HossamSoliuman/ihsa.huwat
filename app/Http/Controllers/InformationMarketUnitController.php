<?php

namespace App\Http\Controllers;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Requests\ManageFishMarketRequest;
use App\Http\Requests\StoreFishMarketUnitRequest;
use App\Http\Requests\UpdateFishMarketUnitRequest;
use App\Models\FishMarket;
use App\Models\FishMarketUnit;
use Illuminate\Http\RedirectResponse;

/**
 * محلات بيع السمك and دكات الحراجات of one market. The routes scope the unit to its market,
 * so a mismatched pair is a 404 rather than an edit of another market's row.
 */
class InformationMarketUnitController extends Controller
{
    public function store(StoreFishMarketUnitRequest $request, FishMarket $market): RedirectResponse
    {
        $unit = $market->units()->create($request->validated());

        return back()->with('status', 'تمت إضافة '.$unit->typeLabel().'.');
    }

    public function update(UpdateFishMarketUnitRequest $request, FishMarket $market, FishMarketUnit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return back()->with('status', 'تم تحديث بيانات '.$unit->typeLabel().'.');
    }

    public function destroy(
        ManageFishMarketRequest $request,
        FishMarket $market,
        FishMarketUnit $unit,
        DeleteMasterDataRecordAction $deleteMasterDataRecord,
    ): RedirectResponse {
        $label = $unit->typeLabel();

        /** Registered عمالة has to be cleared first; the cascade must not do it quietly. */
        $deleteMasterDataRecord->execute($unit, ['fish_market_workers' => 'fish_market_unit_id'], $label);

        return back()->with('status', 'تم حذف '.$label.'.');
    }
}
