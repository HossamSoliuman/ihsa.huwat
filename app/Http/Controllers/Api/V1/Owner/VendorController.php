<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\VendorRequest;
use App\Http\Resources\Api\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = Vendor::forOwner($request->user())->with([])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return VendorResource::collection($rows);
    }

    public function store(VendorRequest $request): JsonResponse
    {
        $row = Vendor::create($request->validated() + ['owner_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return (new VendorResource($row->load([])))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): VendorResource
    {
        return new VendorResource($this->ownedVendor($request->user(), $id)->load([]));
    }

    public function update(VendorRequest $request, int $id): VendorResource
    {
        $row = $this->ownedVendor($request->user(), $id);
        $row->update($request->validated());

        return new VendorResource($row->load([]));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ownedVendor($request->user(), $id)->delete();

        return response()->json(['message' => 'تم حذف المورد.']);
    }
}
