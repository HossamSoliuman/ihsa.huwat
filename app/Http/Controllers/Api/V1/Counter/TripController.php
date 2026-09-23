<?php

namespace App\Http\Controllers\Api\V1\Counter;

use App\Http\Controllers\Concerns\ResolvesCounterRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counter\CountRequest;
use App\Http\Resources\Api\TripReportResource;
use App\Http\Resources\Api\TripResource;
use App\Models\Trip;
use App\Services\Counter\TripReport;
use App\Services\Trips\TripService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * رحلات العدّاد في التطبيق: بحاجة لموافقتك / جارية العد / المعدودة،
 * والاستلام والعد بحسابه هو، والتقرير المفصّل. رحلة ميناء آخر 404.
 */
class TripController extends Controller
{
    use ResolvesCounterRecords;

    private const WITH = ['boat', 'owner', 'captain', 'counter', 'departurePort', 'returnPort', 'tripType'];

    public function __construct(private readonly TripService $trips) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $view = $request->query('view');

        $trips = Trip::forCounter($request->user())->with(self::WITH)
            ->when($view === 'awaiting', fn ($q) => $q->awaitingCounter())
            ->when($view === 'counting', fn ($q) => $q->underCount())
            ->when($view === 'counted', fn ($q) => $q->whereNotNull('counted_at'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('trip_number', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return TripResource::collection($trips);
    }

    public function show(Request $request, int $trip): TripResource
    {
        return new TripResource($this->detailed($this->counterTrip($request->user(), $trip)));
    }

    public function report(Request $request, int $trip, TripReport $report): TripReportResource
    {
        return new TripReportResource($report->for($this->counterTrip($request->user(), $trip)));
    }

    public function receive(Request $request, int $trip): TripResource
    {
        $model = $this->trips->receive($this->counterTrip($request->user(), $trip), $request->user());

        return new TripResource($this->detailed($model));
    }

    public function count(CountRequest $request, int $trip): TripResource
    {
        $model = $this->trips->count(
            $this->counterTrip($request->user(), $trip),
            $request->counted(),
            $request->user(),
            null,
            $request->validated('notes'),
        );

        return new TripResource($this->detailed($model));
    }

    private function detailed(Trip $trip): Trip
    {
        return $trip->load(array_merge(self::WITH, ['catchRecords.species', 'catchRecords.addedBy']));
    }
}
