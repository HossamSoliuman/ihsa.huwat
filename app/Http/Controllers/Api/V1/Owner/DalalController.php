<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\PartnershipRequest;
use App\Http\Resources\Api\PartnershipResource;
use App\Models\DalalPartnership;
use App\Models\Role;
use App\Models\User;
use App\Services\Dalal\PartnershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * الدلالون المفعّلون — لقائمة "إرسال مصيد للدلال"، ومع كلٍّ حال طلب التعامل
 * معه. والمالك يقترح على الدلال عمولته وأجوره من هنا.
 */
class DalalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dalals = User::where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $partnerships = DalalPartnership::forOwner($request->user())->whereIn('dalal_id', $dalals->pluck('id'))->get()->keyBy('dalal_id');

        return response()->json(['data' => $dalals->map(fn (User $dalal) => [
            'id' => $dalal->id,
            'name' => $dalal->name,
            'phone' => $dalal->phone,
            'partnership' => isset($partnerships[$dalal->id]) ? new PartnershipResource($partnerships[$dalal->id]) : null,
        ])]);
    }

    public function partnership(PartnershipRequest $request, int $dalal, PartnershipService $partnerships): JsonResponse
    {
        $model = User::whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))->findOrFail($dalal);

        return (new PartnershipResource($partnerships->request($request->user(), $model, $request->validated())->load('dalal')))->response()->setStatusCode(201);
    }
}
