<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\PaymentRequest;
use App\Http\Requests\Dalal\SaleRequest;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Dalal\DalalStock;
use App\Services\Sales\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مبيعات الدلال من مخزونه: القائمة بفلاترها وبطاقاتها، وإضافة عملية بيع
 * بالسطور، وتفاصيل الفاتورة وتحصيل المتبقي منها.
 */
class SaleController extends Controller
{
    use ResolvesDalalRecords;

    public function __construct(private readonly SaleService $sales) {}

    public function index(Request $request): View
    {
        $dalal = $request->user();

        $query = Sale::forSeller($dalal)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->query('to')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('payment_method_id'), fn ($q) => $q->where('payment_method_id', $request->query('payment_method_id')))
            ->when($request->filled('min'), fn ($q) => $q->where('total', '>=', $request->query('min')))
            ->when($request->filled('max'), fn ($q) => $q->where('total', '<=', $request->query('max')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', '%'.$request->query('search').'%')
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->query('search').'%'))));

        return view('panel.dalal.sales.index', [
            'sales' => (clone $query)->with(['customer', 'paymentMethod', 'paymentStatus'])->withSum('items', 'weight_kg')
                ->orderByDesc('sold_at')->paginate(25)->withQueryString(),
            'totals' => [
                'count' => (clone $query)->count(),
                'revenue' => (float) (clone $query)->sum('total'),
                'weight' => (float) SaleItem::whereIn('sale_id', (clone $query)->select('id'))->sum('weight_kg'),
                'customers' => (clone $query)->whereNotNull('customer_id')->distinct()->count('customer_id'),
            ],
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function create(Request $request, DalalStock $stock): View
    {
        $dalal = $request->user();

        return view('panel.dalal.sales.create', [
            'lots' => $stock->lots($dalal),
            'species' => $stock->bySpecies($dalal),
            'customers' => Customer::forAccount($dalal)->where('status', 'نشط')->orderBy('name')->get(['id', 'name', 'phone']),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $sale = $this->sales->sellFromStock($request->user(), $request->validated());

        return redirect()->route('panel.dalal.sales.show', $sale)->with('status', "سُجّلت الفاتورة {$sale->invoice_number}.");
    }

    public function show(Request $request, int $sale): View
    {
        return view('panel.dalal.sales.show', [
            'sale' => $this->dalalSale($request->user(), $sale)->load(['customer', 'paymentMethod', 'paymentStatus', 'items.species', 'items.trip', 'items.owner']),
        ]);
    }

    public function payment(PaymentRequest $request, int $sale): RedirectResponse
    {
        $model = $this->sales->recordPayment($request->user(), $this->dalalSale($request->user(), $sale), (float) $request->validated('amount'));

        return redirect()->route('panel.dalal.sales.show', $model)->with('status', 'سُجّلت الدفعة.');
    }
}
