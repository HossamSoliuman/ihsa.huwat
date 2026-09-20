<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ConsignmentRequest;
use App\Models\Consignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Sales\SaleService;
use App\Services\Stock\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(
        private readonly SaleService $sales,
        private readonly StockLedger $ledger,
    ) {}

    public function index(Request $request): View
    {
        $rows = Consignment::forOwner($request->user())->with(['dalal', 'trip', 'items.species'])
            ->when($request->filled('dalal'), fn ($q) => $q->where('dalal_id', $request->query('dalal')))
            ->orderByDesc('sent_at')
            ->paginate(25)->withQueryString();

        return view('panel.owner.consignments.index', [
            'rows' => $rows,
            'dalals' => $this->dalals(),
            'totalKg' => (float) Consignment::forOwner($request->user())->sum('total_kg'),
        ]);
    }

    public function create(Request $request): View
    {
        $trip = $this->ownedTrip($request->user(), (int) $request->query('trip'))->load('boat', 'catchRecords.species');

        return view('panel.owner.consignments.create', [
            'trip' => $trip,
            'available' => $this->ledger->availableLines($request->user(), $trip),
            'dalals' => $this->dalals(),
        ]);
    }

    public function store(ConsignmentRequest $request): RedirectResponse
    {
        $consignment = $this->sales->consign($request->user(), $request->validated());

        return redirect()->route('panel.owner.consignments.show', $consignment)->with('status', "أُرسلت الشحنة {$consignment->consignment_number} إلى {$consignment->dalal->name}.");
    }

    public function show(Request $request, int $consignment): View
    {
        return view('panel.owner.consignments.show', [
            'consignment' => $this->ownedConsignment($request->user(), $consignment)->load(['dalal', 'trip.boat', 'items.species']),
        ]);
    }

    private function dalals()
    {
        return User::where('active', true)->whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))->orderBy('name')->get(['id', 'name', 'phone']);
    }
}
