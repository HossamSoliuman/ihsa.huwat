<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CustomerRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = Customer::forAccount($request->user())->with(['customerType', 'region', 'governorate'])->withCount('sales')->withSum('sales', 'total')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return CustomerResource::collection($rows);
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $row = Customer::create($request->validated() + ['account_user_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return (new CustomerResource($row->load(['customerType', 'region', 'governorate'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): CustomerResource
    {
        return new CustomerResource($this->ownedCustomer($request->user(), $id)->load(['customerType', 'region', 'governorate']));
    }

    public function update(CustomerRequest $request, int $id): CustomerResource
    {
        $row = $this->ownedCustomer($request->user(), $id);
        $row->update($request->validated());

        return new CustomerResource($row->load(['customerType', 'region', 'governorate']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ownedCustomer($request->user(), $id)->delete();

        return response()->json(['message' => 'تم حذف العميل.']);
    }
}
