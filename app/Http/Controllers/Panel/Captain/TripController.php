<?php

namespace App\Http\Controllers\Panel\Captain;

use App\Http\Controllers\Concerns\ResolvesCaptainRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CancelTripRequest;
use App\Http\Requests\Owner\CatchRequest;
use App\Models\Species;
use App\Models\Trip;
use App\Services\Trips\TripService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * رحلات الكابتن كما في التطبيق: بانتظارك (ابدأ / إلغاء بسبب)، النشطة
 * (إنهاء الرحلة وإرسال مخرجات المصيد)، وقائمة الرحلات كلها. الأفعال نفسها
 * التي يجريها المالك نيابةً عنه — الخدمة واحدة والطلبات واحدة.
 */
class TripController extends Controller
{
    use ResolvesCaptainRecords;

    public function __construct(private readonly TripService $trips) {}

    public function index(Request $request): View
    {
        $captain = $request->user();
        $view = $request->query('view');

        $trips = Trip::forCaptain($captain)->with(['boat', 'departurePort', 'tripType'])
            ->when($view === 'pending', fn ($q) => $q->awaitingCaptain())
            ->when($view === 'active', fn ($q) => $q->activeForCaptain())
            ->when($view === 'completed', fn ($q) => $q->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED]))
            ->when($view === 'cancelled', fn ($q) => $q->where('status', Trip::CANCELLED))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('trip_number', 'like', '%'.$request->query('search').'%'))
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('panel.captain.trips.index', [
            'trips' => $trips,
            'view' => $view,
            'counts' => [
                'total' => Trip::forCaptain($captain)->count(),
                'pending' => Trip::forCaptain($captain)->awaitingCaptain()->count(),
                'active' => Trip::forCaptain($captain)->activeForCaptain()->count(),
                'completed' => Trip::forCaptain($captain)->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])->count(),
                'cancelled' => Trip::forCaptain($captain)->where('status', Trip::CANCELLED)->count(),
            ],
            'statuses' => Trip::STATUSES,
        ]);
    }

    public function show(Request $request, int $trip): View
    {
        $model = $this->captainTrip($request->user(), $trip)->load([
            'boat', 'owner', 'counter', 'departurePort', 'returnPort', 'tripType',
            'catchRecords.species', 'catchRecords.addedBy',
        ]);

        return view('panel.captain.trips.show', [
            'trip' => $model,
            'species' => Species::where('directory_status', 'نشط')->orderBy('name_ar')->get(['id', 'name_ar']),
        ]);
    }

    public function start(Request $request, int $trip): RedirectResponse
    {
        $model = $this->trips->start($this->captainTrip($request->user(), $trip), $request->user());

        return redirect()->route('panel.captain.trips.show', $model)->with('status', 'انطلقت الرحلة — رحلة موفقة.');
    }

    public function cancel(CancelTripRequest $request, int $trip): RedirectResponse
    {
        $model = $this->trips->cancel($this->captainTrip($request->user(), $trip), $request->validated('reason'), $request->user());

        return redirect()->route('panel.captain.trips.show', $model)->with('status', 'أُلغيت الرحلة وبُلّغ المالك.');
    }

    public function submitCatch(CatchRequest $request, int $trip): RedirectResponse
    {
        $model = $this->trips->submitCatch($this->captainTrip($request->user(), $trip), $request->validated('items'), $request->user());

        return redirect()->route('panel.captain.trips.show', $model)->with('status', 'أُرسلت مخرجات المصيد وانتهت الرحلة — بانتظار العدّاد.');
    }
}
