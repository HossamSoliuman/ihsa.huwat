<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\RejectPartnershipRequest;
use App\Models\DalalPartnership;
use App\Services\Dalal\PartnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * طلبات المالكين: الكل / قيد الانتظار / مقبولة / مرفوضة، وقبول أو رفض.
 */
class PartnershipController extends Controller
{
    use ResolvesDalalRecords;

    public function __construct(private readonly PartnershipService $partnerships) {}

    public function index(Request $request): View
    {
        $dalal = $request->user();
        $status = in_array($request->query('status'), array_keys(DalalPartnership::STATUS_LABELS), true) ? $request->query('status') : null;

        return view('panel.dalal.requests.index', [
            'rows' => DalalPartnership::forDalal($dalal)->with('owner:id,name,phone,email')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest()
                ->paginate(25)->withQueryString(),
            'counts' => DalalPartnership::forDalal($dalal)->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status'),
            'status' => $status,
        ]);
    }

    public function accept(Request $request, int $partnership): RedirectResponse
    {
        $model = $this->partnerships->accept($request->user(), $this->dalalPartnership($request->user(), $partnership));

        return redirect()->route('panel.dalal.requests')->with('status', "قُبل طلب {$model->owner?->name}.");
    }

    public function reject(RejectPartnershipRequest $request, int $partnership): RedirectResponse
    {
        $model = $this->partnerships->reject($request->user(), $this->dalalPartnership($request->user(), $partnership), $request->validated('response_note'));

        return redirect()->route('panel.dalal.requests')->with('status', "رُفض طلب {$model->owner?->name}.");
    }
}
