<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\EquipmentRequest;
use App\Models\Boat;
use App\Models\FishingEquipment;
use App\Models\FishingSeason;
use App\Models\GearType;
use App\Models\Vendor;
use App\Services\Owner\FleetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly FleetService $fleet) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $rows = FishingEquipment::forOwner($owner)
            ->with(['boat', 'gearType', 'vendor', 'seasons', 'expense'])
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('condition'), fn ($q) => $q->where('condition', $request->query('condition')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('purchase_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $all = FishingEquipment::forOwner($owner);

        return view('panel.owner.equipment.index', [
            'rows' => $rows,
            'totals' => [
                'items' => (clone $all)->count(),
                'units' => (int) (clone $all)->sum('quantity'),
                'value' => round((float) (clone $all)->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) AS v')->value('v'), 2),
                'damaged' => (clone $all)->where('condition', '!=', FishingEquipment::CONDITIONS[0])->count(),
            ],
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'gearTypes' => GearType::where('status', 'نشط')->orderBy('name')->get(['id', 'name']),
            'vendors' => Vendor::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'seasons' => FishingSeason::orderBy('name')->get(['id', 'name', 'species', 'start_month', 'end_month', 'status']),
            'conditions' => FishingEquipment::CONDITIONS,
        ]);
    }

    public function store(EquipmentRequest $request): RedirectResponse
    {
        $row = $this->fleet->saveEquipment($request->user(), $request->validated());

        return redirect()->route('panel.owner.equipment')->with('status', 'تمت إضافة المعدات.'.$this->postedNote($row));
    }

    public function update(EquipmentRequest $request, int $equipment): RedirectResponse
    {
        $row = $this->fleet->saveEquipment($request->user(), $request->validated(), $this->ownedEquipment($request->user(), $equipment));

        return redirect()->route('panel.owner.equipment')->with('status', 'تم تحديث المعدات.'.$this->postedNote($row));
    }

    public function destroy(Request $request, int $equipment): RedirectResponse
    {
        $this->fleet->deleteEquipment($request->user(), $this->ownedEquipment($request->user(), $equipment));

        return redirect()->route('panel.owner.equipment')->with('status', 'تم حذف المعدات.');
    }

    private function postedNote(FishingEquipment $row): string
    {
        $expense = $row->expense()->first();

        return $expense ? " تكلفتها في المصروفات ({$expense->expense_number})." : '';
    }
}
