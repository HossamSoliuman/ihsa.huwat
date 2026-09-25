<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\PaymentRequest;
use App\Http\Requests\Dalal\SaleRequest;
use App\Http\Resources\Api\SaleResource;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    use ResolvesDalalRecords;

    private const WITH = ['customer', 'paymentMethod', 'paymentStatus'];

    private const DETAIL = ['items.species', 'items.trip', 'items.owner'];

    public function __construct(private readonly SaleService $sales) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = Sale::forSeller($request->user())->with(self::WITH)->withCount('items')->withSum('items', 'weight_kg')
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->query('customer_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->query('to')))
            ->when($request->filled('min'), fn ($q) => $q->where('total', '>=', $request->query('min')))
            ->when($request->filled('max'), fn ($q) => $q->where('total', '<=', $request->query('max')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', '%'.$request->query('search').'%')
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->query('search').'%'))))
            ->orderByDesc('sold_at')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return SaleResource::collection($sales);
    }

    public function store(SaleRequest $request): JsonResponse
    {
        $sale = $this->sales->sellFromStock($request->user(), $request->validated());

        return (new SaleResource($sale->loadCount('items')->loadSum('items', 'weight_kg')))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $sale): SaleResource
    {
        return new SaleResource($this->dalalSale($request->user(), $sale)->load(array_merge(self::WITH, self::DETAIL))->loadCount('items')->loadSum('items', 'weight_kg'));
    }

    public function payment(PaymentRequest $request, int $sale): SaleResource
    {
        $model = $this->sales->recordPayment($request->user(), $this->dalalSale($request->user(), $sale), (float) $request->validated('amount'));

        return new SaleResource($model->load(array_merge(self::WITH, self::DETAIL))->loadCount('items')->loadSum('items', 'weight_kg'));
    }
}
