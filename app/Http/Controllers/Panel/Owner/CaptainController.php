<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CaptainRequest;
use App\Models\Boat;
use App\Models\IdType;
use App\Models\Port;
use App\Models\Role;
use App\Services\Owner\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * كباتن المالك: حساب دخول للتطبيق فوق سجلّ صياد في الوزارة.
 */
class CaptainController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $captains = $owner->staff()
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::CAPTAIN))
            ->with(['fisher.boat', 'fisher.port', 'fisher.idType'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.captains.index', [
            'captains' => $captains,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'idTypes' => IdType::options(),
        ]);
    }

    public function store(CaptainRequest $request): RedirectResponse
    {
        $this->staff->createCaptain($request->user(), $request->validated());

        return redirect()->route('panel.owner.captains')->with('status', 'تمت إضافة الكابتن — يدخل التطبيق بجواله وكلمة المرور.');
    }

    public function update(CaptainRequest $request, int $captain): RedirectResponse
    {
        $this->staff->updateCaptain($this->ownedCaptain($request->user(), $captain), $request->validated());

        return redirect()->route('panel.owner.captains')->with('status', 'تم تحديث بيانات الكابتن.');
    }

    public function toggle(Request $request, int $captain): RedirectResponse
    {
        $model = $this->ownedCaptain($request->user(), $captain);
        $model->update(['active' => ! $model->active]);
        $model->fisher?->update(['status' => $model->active ? 'نشط' : 'غير نشط']);

        if (! $model->active) {
            $model->tokens()->delete();
        }

        return redirect()->route('panel.owner.captains')->with('status', $model->active ? 'تم تفعيل حساب الكابتن.' : 'تم تعطيل حساب الكابتن.');
    }
}
