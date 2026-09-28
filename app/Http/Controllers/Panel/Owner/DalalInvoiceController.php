<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RejectInvoiceRequest;
use App\Models\DalalInvoiceReview;
use App\Models\User;
use App\Services\Dalal\InvoiceReviewService;
use App\Services\Owner\DalalSettlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * فواتير الدلالين (O5): ما باعه كل دلال من مصيد المالك فاتورةً فاتورة —
 * سطوره وحدها، بحالتي المراجعة والسداد — والقبول والرفض بسبب، وطباعة
 * كشف الفاتورة بسطوره.
 */
class DalalInvoiceController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(
        private readonly DalalSettlement $settlement,
        private readonly InvoiceReviewService $reviews,
    ) {}

    public function index(Request $request): View
    {
        $owner = $request->user();
        $filters = $request->only(['dalal_id', 'status', 'payment', 'from', 'to', 'search']);
        $rows = $this->settlement->invoices($owner, $filters);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return view('panel.owner.dalal-invoices.index', [
            'invoices' => new LengthAwarePaginator($rows->forPage($page, 25)->values(), $rows->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]),
            'totals' => [
                'count' => $rows->count(),
                'owner_net' => round($rows->sum('owner_net'), 2),
                'deductions' => round($rows->sum('deductions'), 2),
                'outstanding' => round($rows->sum('outstanding'), 2),
            ],
            'summary' => $this->settlement->summary($owner),
            'dalals' => User::whereIn('id', $this->settlement->dalalIds($owner))->orderBy('name')->pluck('name', 'id'),
            'statuses' => DalalInvoiceReview::STATUS_LABELS,
            'payments' => DalalSettlement::PAYMENT_LABELS,
        ]);
    }

    public function show(Request $request, int $sale): View
    {
        $review = $this->ownedDalalInvoice($request->user(), $sale);

        return view('panel.owner.dalal-invoices.show', [
            'review' => $review->load('reviewer', 'dalal.dalalProfile.port', 'sale.customer'),
        ] + $this->settlement->invoice($request->user(), $review));
    }

    public function print(Request $request, int $sale): View
    {
        $review = $this->ownedDalalInvoice($request->user(), $sale);

        return view('panel.owner.dalal-invoices.print', [
            'owner' => $request->user(),
            'review' => $review->load('dalal.dalalProfile', 'sale.customer'),
        ] + $this->settlement->invoice($request->user(), $review));
    }

    public function accept(Request $request, int $sale): RedirectResponse
    {
        $review = $this->reviews->accept($request->user(), $this->ownedDalalInvoice($request->user(), $sale));

        return redirect()->route('panel.owner.dalal-invoices.show', $review->sale_id)->with('status', "قُبلت الفاتورة {$review->sale->invoice_number} وأُبلغ الدلال.");
    }

    public function reject(RejectInvoiceRequest $request, int $sale): RedirectResponse
    {
        $review = $this->reviews->reject($request->user(), $this->ownedDalalInvoice($request->user(), $sale), $request->validated('reason'));

        return redirect()->route('panel.owner.dalal-invoices.show', $review->sale_id)->with('status', "رُفضت الفاتورة {$review->sale->invoice_number} وأُرسل السبب إلى الدلال.");
    }

    /**
     * قبول كل الفواتير قيد المراجعة — من دلال واحد إن اختير في التصفية.
     */
    public function acceptAll(Request $request): RedirectResponse
    {
        $owner = $request->user();
        $dalal = $request->filled('dalal_id') ? $this->settlement->linkedDalal($owner, $request->input('dalal_id')) : null;
        $count = $this->reviews->acceptPending($owner, $dalal);

        return redirect()->route('panel.owner.dalal-invoices', array_filter(['dalal_id' => $dalal?->id]))
            ->with('status', $count > 0 ? "قُبلت {$count} فاتورة قيد المراجعة." : 'لا فواتير قيد المراجعة.');
    }
}
