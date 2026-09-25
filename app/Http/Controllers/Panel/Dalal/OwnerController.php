<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\PayoutRequest;
use App\Models\DalalPayout;
use App\Models\PaymentMethod;
use App\Models\Region;
use App\Services\Dalal\DalalAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الصيّادون المرتبطون: من أرسل إلى الدلال مصيدًا أو قُبل طلبه، بحساب كلٍّ
 * منهم (المستلم، المباع، صافيه، المدفوع، المستحق) ودفعاته.
 */
class OwnerController extends Controller
{
    public function __construct(private readonly DalalAccounts $accounts) {}

    public function index(Request $request): View
    {
        $dalal = $request->user();

        return view('panel.dalal.owners.index', [
            'rows' => $this->accounts->owners($dalal, $request->query('search'), $request->integer('region_id') ?: null),
            'payouts' => DalalPayout::forDalal($dalal)->with(['owner:id,name', 'paymentMethod'])->latest('paid_at')->limit(15)->get(),
            'regions' => Region::orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function payout(PayoutRequest $request, int $owner): RedirectResponse
    {
        $dalal = $request->user();
        $payout = $this->accounts->recordPayout($dalal, $this->accounts->linkedOwner($dalal, $owner), $request->validated());

        return redirect()->route('panel.dalal.owners')->with('status', 'سُجّلت دفعة '.number_format((float) $payout->amount, 2)." ر.س إلى {$payout->owner->name}.");
    }
}
