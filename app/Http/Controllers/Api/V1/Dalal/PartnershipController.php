<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\RejectPartnershipRequest;
use App\Http\Resources\Api\PartnershipResource;
use App\Models\DalalPartnership;
use App\Services\Dalal\PartnershipService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PartnershipController extends Controller
{
    use ResolvesDalalRecords;

    public function __construct(private readonly PartnershipService $partnerships) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = DalalPartnership::forDalal($request->user())->with('owner')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return PartnershipResource::collection($rows);
    }

    public function accept(Request $request, int $partnership): PartnershipResource
    {
        return new PartnershipResource($this->partnerships->accept($request->user(), $this->dalalPartnership($request->user(), $partnership)));
    }

    public function reject(RejectPartnershipRequest $request, int $partnership): PartnershipResource
    {
        return new PartnershipResource($this->partnerships->reject($request->user(), $this->dalalPartnership($request->user(), $partnership), $request->validated('response_note')));
    }
}
