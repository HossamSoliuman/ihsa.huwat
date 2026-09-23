<?php

namespace App\Http\Controllers\Panel\Counter;

use App\Http\Controllers\Concerns\ResolvesCounterRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counter\CountRequest;
use App\Models\Species;
use App\Models\Trip;
use App\Services\Counter\TripReport;
use App\Services\Trips\TripService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * طابور العدّاد كما في التطبيق: رحلات بحاجة لموافقتك (استلام)، الرحلات
 * النشطة (عد المصيد صنفًا صنفًا مع فحص الكمية وإضافة صنف)، ثم التأكيد الذي
 * يغذّي صفحات الإحصاء ويفتح مصيد الرحلة للبيع عند مالكها — الخدمة نفسها
 * التي يستدعيها الإحصاء الميداني (TripService::receive/count).
 */
class TripController extends Controller
{
    use ResolvesCounterRecords;

    public function __construct(private readonly TripService $trips) {}

    public function index(Request $request): View
    {
        $counter = $request->user();
        $view = $request->query('view');

        $trips = Trip::forCounter($counter)->with(['boat', 'owner', 'captain', 'returnPort', 'departurePort', 'tripType'])
            ->when($view === 'awaiting', fn ($q) => $q->awaitingCounter())
            ->when($view === 'counting', fn ($q) => $q->underCount())
            ->when($view === 'counted', fn ($q) => $q->whereNotNull('counted_at'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('trip_number', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('panel.counter.trips.index', [
            'trips' => $trips,
            'view' => $view,
            'port' => $counter->statisticsOfficer?->port,
            'counts' => [
                'total' => Trip::forCounter($counter)->count(),
                'awaiting' => Trip::forCounter($counter)->awaitingCounter()->count(),
                'counting' => Trip::forCounter($counter)->underCount()->count(),
                'counted' => Trip::forCounter($counter)->whereNotNull('counted_at')->count(),
            ],
            'statuses' => Trip::COUNTER_STATUSES,
        ]);
    }

    public function show(Request $request, int $trip): View
    {
        $model = $this->counterTrip($request->user(), $trip)->load([
            'boat', 'owner', 'captain', 'counter', 'departurePort', 'returnPort', 'tripType',
            'catchRecords.species', 'catchRecords.addedBy',
        ]);

        return view('panel.counter.trips.show', [
            'trip' => $model,
            'species' => Species::where('directory_status', 'نشط')->orderBy('name_ar')->get(['id', 'name_ar']),
        ]);
    }

    /**
     * "تقرير مفصّل للرحلة" — شاشة قائمة بذاتها في التطبيق.
     */
    public function report(Request $request, int $trip, TripReport $report): View
    {
        return view('panel.counter.trips.report', $report->for($this->counterTrip($request->user(), $trip)));
    }

    public function receive(Request $request, int $trip): RedirectResponse
    {
        $model = $this->trips->receive($this->counterTrip($request->user(), $trip), $request->user());

        return redirect()->route('panel.counter.trips.show', $model)->with('status', 'استُلمت الرحلة — ابدأ عدّ المصيد.');
    }

    public function count(CountRequest $request, int $trip): RedirectResponse
    {
        $model = $this->trips->count(
            $this->counterTrip($request->user(), $trip),
            $request->counted(),
            $request->user(),
            null,
            $request->validated('notes'),
        );

        return redirect()->route('panel.counter.trips.show', $model)
            ->with('status', 'اكتمل العد: '.number_format((float) $model->actual_weight_kg, 1).' كجم — أُرسل للاعتماد وفُتح المصيد للبيع.');
    }
}
