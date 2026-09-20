<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\EmployeeRequest;
use App\Http\Resources\Api\EmployeeResource;
use App\Models\OwnerEmployee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = OwnerEmployee::forOwner($request->user())->with(['jobTitle'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return EmployeeResource::collection($rows);
    }

    public function store(EmployeeRequest $request): JsonResponse
    {
        $row = OwnerEmployee::create($request->validated() + ['owner_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return (new EmployeeResource($row->load(['jobTitle'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): EmployeeResource
    {
        return new EmployeeResource($this->ownedEmployee($request->user(), $id)->load(['jobTitle']));
    }

    public function update(EmployeeRequest $request, int $id): EmployeeResource
    {
        $row = $this->ownedEmployee($request->user(), $id);
        $row->update($request->validated());

        return new EmployeeResource($row->load(['jobTitle']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->ownedEmployee($request->user(), $id)->delete();

        return response()->json(['message' => 'تم حذف الموظف.']);
    }
}
