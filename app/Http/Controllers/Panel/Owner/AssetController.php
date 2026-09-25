<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AssetRequest;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Boat;
use App\Models\User;
use App\Services\Owner\AssetDepreciation;
use App\Services\Owner\FleetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(
        private readonly FleetService $fleet,
        private readonly AssetDepreciation $depreciation,
    ) {}

    /**
     * سجل الأصول: كل أصل بإهلاكه المتراكم وقيمته الدفترية اليوم.
     */
    public function index(Request $request): View
    {
        $owner = $request->user();

        return view('panel.owner.assets.index', [
            'register' => $this->depreciation->register($this->filtered($request, $owner)),
            'thisMonth' => $this->depreciation->forMonth($owner, now()->year, now()->month)['total'],
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'types' => AssetType::options(),
            'statuses' => Asset::STATUSES,
        ]);
    }

    /**
     * جدول إهلاك السنة شهرًا شهرًا وتفصيله لكل أصل.
     */
    public function depreciation(Request $request): View
    {
        return view('panel.owner.assets.depreciation', $this->schedule($request) + [
            'boats' => Boat::forOwner($request->user())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function registerPrint(Request $request): View
    {
        $owner = $request->user();

        return view('panel.owner.assets.register-print', [
            'owner' => $owner,
            'register' => $this->depreciation->register($this->filtered($request, $owner)),
        ]);
    }

    public function depreciationPrint(Request $request): View
    {
        return view('panel.owner.assets.depreciation-print', $this->schedule($request) + ['owner' => $request->user()]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        $this->fleet->saveAsset($request->user(), $request->validated());

        return redirect()->route('panel.owner.assets')->with('status', 'تمت إضافة الأصل.');
    }

    public function update(AssetRequest $request, int $asset): RedirectResponse
    {
        $this->fleet->saveAsset($request->user(), $request->validated(), $this->ownedAsset($request->user(), $asset));

        return redirect()->route('panel.owner.assets')->with('status', 'تم تحديث الأصل.');
    }

    public function destroy(Request $request, int $asset): RedirectResponse
    {
        $this->ownedAsset($request->user(), $asset)->delete();

        return redirect()->route('panel.owner.assets')->with('status', 'تم حذف الأصل.');
    }

    private function filtered(Request $request, User $owner)
    {
        return Asset::forOwner($owner)
            ->with(['type', 'boat'])
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('type'), fn ($q) => $q->where('asset_type_id', $request->query('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('purchase_date')->orderBy('id')
            ->get();
    }

    private function schedule(Request $request): array
    {
        $owner = $request->user();
        $year = (int) $request->query('year', now()->year);
        $boatId = $request->filled('boat') ? (int) $request->query('boat') : null;
        $earliest = Asset::forOwner($owner)->min('purchase_date');
        $firstYear = min($earliest ? (int) substr($earliest, 0, 4) : now()->year, now()->year);

        return [
            'year' => $year,
            'years' => range(now()->year + 1, $firstYear),
            'boat' => $boatId ? Boat::forOwner($owner)->find($boatId) : null,
            'schedule' => $this->depreciation->forYear($owner, $year, $boatId),
        ];
    }
}
