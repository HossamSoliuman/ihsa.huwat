<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildTripDashboardAction;
use App\Actions\DeleteMasterDataRecordAction;
use App\Actions\MarkTripArrivedAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterTripsRequest;
use App\Http\Requests\ManageTripRequest;
use App\Http\Requests\StoreTripRequest;
use App\Models\Boat;
use App\Models\Captain;
use App\Models\Port;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TripController extends Controller
{
    public function index(FilterTripsRequest $request, BuildTripDashboardAction $action): View
    {
        return view('dashboard.trips.index', $action->execute($request->user(), $request->validated()));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('create', Trip::class), 403);

        return view('dashboard.trips.create', [
            'boats' => Boat::query()->orderBy('name')->get(),
            'captains' => Captain::query()->orderBy('full_name')->get(),
            'ports' => Port::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTripRequest $request): RedirectResponse
    {
        Trip::query()->create([...$request->validated(), 'status' => 'expected']);

        return to_route('dashboard.trips.index')->with('status', 'تمت إضافة الرحلة بحالة متوقعة.');
    }

    public function arrive(ManageTripRequest $request, Trip $trip, MarkTripArrivedAction $action): RedirectResponse
    {
        $action->execute($trip);

        return back()->with('status', 'تم تسجيل وصول القارب.');
    }

    public function destroy(ManageTripRequest $request, Trip $trip, DeleteMasterDataRecordAction $action): RedirectResponse
    {
        $action->execute($trip, ['catch_details' => 'trip_id', 'trip_discrepancies' => 'trip_id'], 'الرحلة');

        return back()->with('status', 'تم حذف الرحلة.');
    }
}
