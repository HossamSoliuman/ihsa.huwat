<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\MaintenanceRequest;
use App\Models\Boat;
use App\Models\BoatMaintenance;
use App\Models\Expense;
use App\Models\MaintenanceType;
use App\Services\Owner\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly ExpenseService $expenses) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $rows = BoatMaintenance::whereHas('boat', fn ($q) => $q->where('owner_id', $owner->id))
            ->with(['boat', 'maintenanceType', 'expense'])
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
        $row = BoatMaintenance::create($request->validated() + ['status' => $request->input('status', 'معلقة')]);
        $expense = $this->expenses->syncSource($row, $request->user());

        return redirect()->route('panel.owner.maintenance')->with('status', 'تمت إضافة سجل الصيانة.'.$this->postedNote($expense));
    }

    public function update(MaintenanceRequest $request, int $maintenance): RedirectResponse
    {
        $row = $this->ownedMaintenance($request->user(), $maintenance);
        $row->update($request->validated());
        $expense = $this->expenses->syncSource($row, $request->user());

        return redirect()->route('panel.owner.maintenance')->with('status', 'تم تحديث سجل الصيانة.'.$this->postedNote($expense));
    }

    public function destroy(Request $request, int $maintenance): RedirectResponse
    {
        $row = $this->ownedMaintenance($request->user(), $maintenance);
        $this->expenses->releaseSource($row, $request->user());
        $row->delete();

        return redirect()->route('panel.owner.maintenance')->with('status', 'تم حذف سجل الصيانة.');
    }

    private function postedNote(?Expense $expense): string
    {
        return $expense ? " رُحِّلت تكلفتها إلى المصروفات ({$expense->expense_number})." : '';
    }
}
