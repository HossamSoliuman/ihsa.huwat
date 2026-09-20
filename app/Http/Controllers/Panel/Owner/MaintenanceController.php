<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\MaintenanceRequest;
use App\Models\Boat;
use App\Models\BoatMaintenance;
use App\Models\MaintenanceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): View
    {
        $owner = $request->user();

        $rows = BoatMaintenance::whereHas('boat', fn ($q) => $q->where('owner_id', $owner->id))
            ->with(['boat', 'maintenanceType'])
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('date')
            ->paginate(25)->withQueryString();

        return view('panel.owner.maintenance.index', [
            'rows' => $rows,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'types' => MaintenanceType::options(),
            'statuses' => BoatMaintenance::STATUSES,
        ]);
    }

    public function store(MaintenanceRequest $request): RedirectResponse
    {
        BoatMaintenance::create($request->validated() + ['status' => $request->input('status', 'معلقة')]);

        return redirect()->route('panel.owner.maintenance')->with('status', 'تمت إضافة سجل الصيانة.');
    }

    public function update(MaintenanceRequest $request, int $maintenance): RedirectResponse
    {
        $this->ownedMaintenance($request->user(), $maintenance)->update($request->validated());

        return redirect()->route('panel.owner.maintenance')->with('status', 'تم تحديث سجل الصيانة.');
    }

    public function destroy(Request $request, int $maintenance): RedirectResponse
    {
        $this->ownedMaintenance($request->user(), $maintenance)->delete();

        return redirect()->route('panel.owner.maintenance')->with('status', 'تم حذف سجل الصيانة.');
    }
}
