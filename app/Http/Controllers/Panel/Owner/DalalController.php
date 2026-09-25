<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\PartnershipRequest;
use App\Models\Consignment;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\Role;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Dalal\PartnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الدلالون من جهة المالك: كل دلال مفعّل بحال طلب التعامل معه (العمولة
 * والأجور المقترحة)، وما أرسله إليه وما بيع منه وصافيه والمدفوع والمستحق.
 */
class DalalController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user();

        $dalals = User::where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))
            ->with('dalalProfile.port')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')->get(['id', 'name', 'phone']);

        $sent = Consignment::forOwner($owner)->selectRaw('dalal_id, SUM(total_kg) AS kg')->groupBy('dalal_id')->pluck('kg', 'dalal_id');
        $sold = SaleItem::where('owner_id', $owner->id)->join('sales', 'sales.id', '=', 'sale_items.sale_id')->whereColumn('sales.seller_id', '!=', 'sale_items.owner_id')
            ->selectRaw('sales.seller_id AS dalal_id, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.owner_net) AS net')
            ->groupBy('sales.seller_id')->get()->keyBy('dalal_id');
        $paid = DalalPayout::where('owner_id', $owner->id)->selectRaw('dalal_id, SUM(amount) AS amount')->groupBy('dalal_id')->pluck('amount', 'dalal_id');

        return view('panel.owner.dalals.index', [
            'dalals' => $dalals,
            'partnerships' => DalalPartnership::forOwner($owner)->get()->keyBy('dalal_id'),
            'accounts' => $dalals->mapWithKeys(fn (User $dalal) => [$dalal->id => [
                'sent_kg' => round((float) ($sent[$dalal->id] ?? 0), 2),
                'sold_kg' => round((float) ($sold[$dalal->id]->kg ?? 0), 2),
                'net' => round((float) ($sold[$dalal->id]->net ?? 0), 2),
                'paid' => round((float) ($paid[$dalal->id] ?? 0), 2),
            ]]),
        ]);
    }

    public function request(PartnershipRequest $request, int $dalal, PartnershipService $partnerships): RedirectResponse
    {
        $model = User::whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))->findOrFail($dalal);
        $partnerships->request($request->user(), $model, $request->validated());

        return redirect()->route('panel.owner.dalals')->with('status', "أُرسل طلب التعامل إلى {$model->name}.");
    }
}
