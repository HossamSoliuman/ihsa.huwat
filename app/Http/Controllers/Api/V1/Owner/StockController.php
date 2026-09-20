<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\StockMovementResource;
use App\Models\StockMovement;
use App\Services\Stock\StockLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * الأسماك المتوفرة عند المالك وحركات الصنف.
 */
class StockController extends Controller
{
    public function __construct(private readonly StockLedger $ledger) {}

    public function index(Request $request): JsonResponse
    {
        $stock = $this->ledger->stockBySpecies($request->user())
            ->map(fn ($row) => ['species_id' => $row->species_id, 'species' => $row->species?->name_ar, 'name_sci' => $row->species?->name_sci, 'weight_kg' => round((float) $row->kg, 2)])
            ->values();

        return response()->json(['data' => $stock]);
    }

    public function movements(Request $request): AnonymousResourceCollection
    {
        $rows = StockMovement::forHolder($request->user())->with(['species:id,name_ar', 'type:id,name', 'trip:id,trip_number', 'user:id,name'])
            ->when($request->filled('species_id'), fn ($q) => $q->where('species_id', $request->query('species_id')))
            ->when($request->filled('trip_id'), fn ($q) => $q->where('trip_id', $request->query('trip_id')))
            ->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return StockMovementResource::collection($rows);
    }
}
