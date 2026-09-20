<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ConsignmentRequest;
use App\Http\Resources\Api\ConsignmentResource;
use App\Models\Consignment;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConsignmentController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['dalal', 'trip', 'items.species'];

    public function __construct(private readonly SaleService $sales) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = Consignment::forOwner($request->user())->with(self::WITH)
            ->when($request->filled('trip_id'), fn ($q) => $q->where('trip_id', $request->query('trip_id')))
            ->when($request->filled('dalal_id'), fn ($q) => $q->where('dalal_id', $request->query('dalal_id')))
            ->orderByDesc('sent_at')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return ConsignmentResource::collection($rows);
    }

    public function store(ConsignmentRequest $request): JsonResponse
    {
        $consignment = $this->sales->consign($request->user(), $request->validated());

        return (new ConsignmentResource($consignment))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $consignment): ConsignmentResource
    {
        return new ConsignmentResource($this->ownedConsignment($request->user(), $consignment)->load(self::WITH));
    }
}
