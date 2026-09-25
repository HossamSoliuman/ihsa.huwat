<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\CustomerRequest;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    use ResolvesDalalRecords;

    private const WITH = ['customerType', 'region', 'governorate'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = Customer::forAccount($request->user())->with(self::WITH)->withCount('sales')->withSum('sales', 'total')
            ->when($request->filled('region_id'), fn ($q) => $q->where('region_id', $request->query('region_id')))
            ->when($request->filled('governorate_id'), fn ($q) => $q->where('governorate_id', $request->query('governorate_id')))
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

        return (new CustomerResource($row->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): CustomerResource
    {
        return new CustomerResource($this->dalalCustomer($request->user(), $id)->load(self::WITH));
    }

    public function update(CustomerRequest $request, int $id): CustomerResource
    {
        $row = $this->dalalCustomer($request->user(), $id);
        $row->update($request->validated());

        return new CustomerResource($row->load(self::WITH));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->dalalCustomer($request->user(), $id)->delete();

        return response()->json(['message' => 'تم حذف العميل.']);
    }
}
