<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CrewRequest;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\IdType;
use App\Models\Port;
use App\Services\Owner\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrewController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $crew = Fisher::forOwner($owner)->crew()->with(['boat', 'port', 'fisherRole', 'idType'])
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.crew.index', [
            'crew' => $crew,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'roles' => FisherRole::options(),
            'idTypes' => IdType::options(),
        ]);
    }

    public function store(CrewRequest $request): RedirectResponse
    {
        $this->staff->createCrew($request->user(), $request->validated());

        return redirect()->route('panel.owner.crew')->with('status', 'تمت إضافة عضو الطاقم.');
    }

    public function update(CrewRequest $request, int $crew): RedirectResponse
    {
        $this->staff->updateCrew($this->ownedCrew($request->user(), $crew), $request->validated());

        return redirect()->route('panel.owner.crew')->with('status', 'تم تحديث عضو الطاقم.');
    }

    public function destroy(Request $request, int $crew): RedirectResponse
    {
        $this->ownedCrew($request->user(), $crew)->delete();

        return redirect()->route('panel.owner.crew')->with('status', 'تم حذف عضو الطاقم.');
    }
}
