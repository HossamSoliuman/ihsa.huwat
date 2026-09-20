<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CaptainRequest;
use App\Http\Resources\Api\CaptainResource;
use App\Models\Role;
use App\Services\Owner\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CaptainController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['fisher.boat', 'fisher.port', 'fisher.idType', 'fisher.fisherRole'];

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $captains = $request->user()->staff()
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::CAPTAIN))
            ->with(self::WITH)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return CaptainResource::collection($captains);
    }

    public function store(CaptainRequest $request): JsonResponse
    {
        $captain = $this->staff->createCaptain($request->user(), $request->validated());

        return (new CaptainResource($captain->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $captain): CaptainResource
    {
        return new CaptainResource($this->ownedCaptain($request->user(), $captain)->load(self::WITH));
    }

    public function update(CaptainRequest $request, int $captain): CaptainResource
    {
        $model = $this->staff->updateCaptain($this->ownedCaptain($request->user(), $captain), $request->validated());

        return new CaptainResource($model->load(self::WITH));
    }
}
