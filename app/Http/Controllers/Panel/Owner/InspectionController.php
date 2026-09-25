<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\InspectionRequest;
use App\Models\Boat;
use App\Models\BoatInspection;
use App\Services\Owner\FleetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectionController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly FleetService $fleet) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        return view('panel.owner.inspections.index', [
            'rows' => BoatInspection::forOwner($owner)
                ->with('boat')
                ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
                ->when($request->filled('result'), fn ($q) => $q->where('result', $request->query('result')))
                ->orderByDesc('inspection_date')->orderByDesc('id')
                ->paginate(25)->withQueryString(),
            // موقف كل قارب: آخر فحص وموعد القادم.
            'fleet' => Boat::forOwner($owner)
                ->with(['inspections' => fn ($q) => $q->orderByDesc('inspection_date')->orderByDesc('id')->limit(1)])
                ->orderBy('name')->get(['id', 'name', 'next_inspection_date']),
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'results' => BoatInspection::RESULTS,
        ]);
    }

    public function store(InspectionRequest $request): RedirectResponse
    {
        $this->fleet->saveInspection($request->validated(), $request->file('attachment'));

        return redirect()->route('panel.owner.inspections')->with('status', 'تم تسجيل الفحص.');
    }

    public function update(InspectionRequest $request, int $inspection): RedirectResponse
    {
        $row = $this->ownedInspection($request->user(), $inspection);
        $this->fleet->saveInspection($request->validated(), $request->file('attachment'), $request->boolean('remove_attachment'), $row);

        return redirect()->route('panel.owner.inspections')->with('status', 'تم تحديث الفحص.');
    }

    public function destroy(Request $request, int $inspection): RedirectResponse
    {
        $this->fleet->deleteInspection($this->ownedInspection($request->user(), $inspection));

        return redirect()->route('panel.owner.inspections')->with('status', 'تم حذف الفحص.');
    }
}
