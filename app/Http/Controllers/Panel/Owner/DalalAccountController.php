<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DalalReceiptRequest;
use App\Models\PaymentMethod;
use App\Services\Owner\DalalSettlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * حسابات الدلالين (O5): رصيد المالك عند كل دلال تعامل معه، وكشف الحساب
 * بالفواتير والدفعات ورصيد جارٍ، وطباعته، وتسجيل ما استلمه منه.
 */
class DalalAccountController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly DalalSettlement $settlement) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        return view('panel.owner.dalal-accounts.index', [
            'rows' => $this->settlement->accounts($owner, $request->query('search')),
            'summary' => $this->settlement->summary($owner),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function show(Request $request, int $dalal): View
    {
        $owner = $request->user();
        $model = $this->settlement->linkedDalal($owner, $dalal);

        return view('panel.owner.dalal-accounts.show', [
            'dalal' => $model->load('dalalProfile.port'),
            'account' => $this->settlement->accounts($owner)->firstWhere('id', $model->id),
            'statement' => $this->settlement->statement($owner, $model, $request->query('from'), $request->query('to')),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function print(Request $request, int $dalal): View
    {
        $owner = $request->user();
        $model = $this->settlement->linkedDalal($owner, $dalal);

        return view('panel.owner.dalal-accounts.print', [
            'owner' => $owner,
            'dalal' => $model->load('dalalProfile'),
            'account' => $this->settlement->accounts($owner)->firstWhere('id', $model->id),
            'statement' => $this->settlement->statement($owner, $model, $request->query('from'), $request->query('to')),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    }

    public function receipt(DalalReceiptRequest $request, int $dalal): RedirectResponse
    {
        $owner = $request->user();
        $payout = $this->settlement->recordReceipt($owner, $this->settlement->linkedDalal($owner, $dalal), $request->validated());

        return redirect()->route('panel.owner.dalal-accounts.show', $dalal)
            ->with('status', 'سُجّل استلام '.number_format((float) $payout->amount, 2).' ر.س من '.$payout->dalal->name.'.');
    }

    public function destroyReceipt(Request $request, int $dalal, int $payout): RedirectResponse
    {
        $owner = $request->user();
        $model = $this->settlement->linkedDalal($owner, $dalal);
        $this->settlement->deleteReceipt($owner, $this->ownedDalalPayout($owner, $model, $payout));

        return redirect()->route('panel.owner.dalal-accounts.show', $dalal)->with('status', 'حُذف الاستلام.');
    }
}
