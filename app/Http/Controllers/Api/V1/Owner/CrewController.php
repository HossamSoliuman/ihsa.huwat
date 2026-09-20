<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CrewRequest;
use App\Http\Resources\Api\FisherResource;
use App\Models\Fisher;
use App\Services\Owner\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CrewController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['boat', 'port', 'idType', 'fisherRole'];

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $crew = Fisher::forOwner($request->user())->crew()->with(self::WITH)
            ->when($request->filled('boat_id'), fn ($q) => $q->where('boat_id', $request->query('boat_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return FisherResource::collection($crew);
    }

    public function store(CrewRequest $request): JsonResponse
    {
        $fisher = $this->staff->createCrew($request->user(), $request->validated());

        return (new FisherResource($fisher->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function update(CrewRequest $request, int $crew): FisherResource
    {
        $fisher = $this->staff->updateCrew($this->ownedCrew($request->user(), $crew), $request->validated());

        return new FisherResource($fisher->load(self::WITH));
    }

    public function destroy(Request $request, int $crew): JsonResponse
    {
        $this->ownedCrew($request->user(), $crew)->delete();

        return response()->json(['message' => 'تم حذف عضو الطاقم.']);
    }
}
