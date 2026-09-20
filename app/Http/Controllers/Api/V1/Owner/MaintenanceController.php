<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\MaintenanceRequest;
use App\Http\Resources\Api\MaintenanceResource;
use App\Models\BoatMaintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = BoatMaintenance::whereHas('boat', fn ($q) => $q->where('owner_id', $request->user()->id))
            ->with(['boat', 'maintenanceType'])
            ->when($request->filled('boat_id'), fn ($q) => $q->where('boat_id', $request->query('boat_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderByDesc('date')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return MaintenanceResource::collection($rows);
    }

    public function store(MaintenanceRequest $request): JsonResponse
    {
        $row = BoatMaintenance::create($request->validated() + ['status' => $request->input('status', 'معلقة')]);

        return (new MaintenanceResource($row->load(['boat', 'maintenanceType'])))->response()->setStatusCode(201);
    }

    public function update(MaintenanceRequest $request, int $maintenance): MaintenanceResource
    {
        $row = $this->ownedMaintenance($request->user(), $maintenance);
        $row->update($request->validated());

        return new MaintenanceResource($row->load(['boat', 'maintenanceType']));
    }

    public function destroy(Request $request, int $maintenance): JsonResponse
    {
        $this->ownedMaintenance($request->user(), $maintenance)->delete();

        return response()->json(['message' => 'تم حذف سجل الصيانة.']);
    }
}
