<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManageFishMarketRequest;
use App\Http\Requests\StoreFishMarketWorkerRequest;
use App\Http\Requests\UpdateFishMarketWorkerRequest;
use App\Models\FishMarket;
use App\Models\FishMarketUnit;
use App\Models\FishMarketWorker;
use Illuminate\Http\RedirectResponse;

/** العمالة of one shop or auction stall, scoped to the unit and the market above it. */
class InformationMarketWorkerController extends Controller
{
    public function store(
        StoreFishMarketWorkerRequest $request,
        FishMarket $market,
        FishMarketUnit $unit,
    ): RedirectResponse {
        $unit->workers()->create($request->validated());

        return back()->with('status', 'تمت إضافة سجل العمالة.');
    }

    public function update(
        UpdateFishMarketWorkerRequest $request,
        FishMarket $market,
        FishMarketUnit $unit,
        FishMarketWorker $worker,
    ): RedirectResponse {
        $worker->update($request->validated());

        return back()->with('status', 'تم تحديث سجل العمالة.');
    }

    public function destroy(
        ManageFishMarketRequest $request,
        FishMarket $market,
        FishMarketUnit $unit,
        FishMarketWorker $worker,
    ): RedirectResponse {
        $worker->delete();

        return back()->with('status', 'تم حذف سجل العمالة.');
    }
}
