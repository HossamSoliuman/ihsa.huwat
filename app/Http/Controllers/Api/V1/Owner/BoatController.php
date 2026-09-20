<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BoatRequest;
use App\Http\Resources\Api\BoatResource;
use App\Models\Boat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoatController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['port', 'category', 'type', 'captainUser'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $boats = Boat::forOwner($request->user())->with(self::WITH)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('boat_number', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return BoatResource::collection($boats);
    }

    public function store(BoatRequest $request): JsonResponse
    {
        $boat = Boat::create($request->validated() + ['owner_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return (new BoatResource($boat->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $boat): BoatResource
    {
        return new BoatResource($this->ownedBoat($request->user(), $boat)->load(self::WITH));
    }

    public function update(BoatRequest $request, int $boat): BoatResource
    {
        $model = $this->ownedBoat($request->user(), $boat);
        $model->update($request->validated());

        return new BoatResource($model->load(self::WITH));
    }

    public function destroy(Request $request, int $boat): JsonResponse
    {
        $model = $this->ownedBoat($request->user(), $boat);

        if ($model->trips()->exists()) {
            return response()->json(['message' => 'لا يُحذف قارب له رحلات — عطّله بدل حذفه.'], 422);
        }

        $model->delete();

        return response()->json(['message' => 'تم حذف القارب.']);
    }
}
