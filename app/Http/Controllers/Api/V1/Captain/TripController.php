<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Http\Controllers\Concerns\ResolvesCaptainRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CancelTripRequest;
use App\Http\Requests\Owner\CatchRequest;
use App\Http\Resources\Api\TripResource;
use App\Models\Trip;
use App\Services\Trips\TripService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * رحلات الكابتن في التطبيق: بانتظارك / النشطة / القائمة، والبدء والإلغاء
 * وإرسال المخرجات بحسابه هو. الرحلة المسندة لغيره 404.
 */
class TripController extends Controller
{
    use ResolvesCaptainRecords;

    private const WITH = ['boat', 'captain', 'counter', 'departurePort', 'returnPort', 'tripType'];

    public function __construct(private readonly TripService $trips) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $view = $request->query('view');

        $trips = Trip::forCaptain($request->user())->with(self::WITH)
            ->when($view === 'pending', fn ($q) => $q->awaitingCaptain())
            ->when($view === 'active', fn ($q) => $q->activeForCaptain())
            ->when($view === 'completed', fn ($q) => $q->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED]))
            ->when($view === 'cancelled', fn ($q) => $q->where('status', Trip::CANCELLED))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('trip_number', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return TripResource::collection($trips);
    }

    public function show(Request $request, int $trip): TripResource
    {
        return new TripResource($this->detailed($this->captainTrip($request->user(), $trip)));
    }

    public function start(Request $request, int $trip): TripResource
    {
        return new TripResource($this->trips->start($this->captainTrip($request->user(), $trip), $request->user())->load(self::WITH));
    }

    public function cancel(CancelTripRequest $request, int $trip): TripResource
    {
        return new TripResource($this->trips->cancel($this->captainTrip($request->user(), $trip), $request->validated('reason'), $request->user())->load(self::WITH));
    }

    public function submitCatch(CatchRequest $request, int $trip): TripResource
    {
        $model = $this->trips->submitCatch($this->captainTrip($request->user(), $trip), $request->validated('items'), $request->user());

        return new TripResource($this->detailed($model));
    }

    private function detailed(Trip $trip): Trip
    {
        return $trip->load(array_merge(self::WITH, ['catchRecords.species', 'catchRecords.addedBy']));
    }
}
