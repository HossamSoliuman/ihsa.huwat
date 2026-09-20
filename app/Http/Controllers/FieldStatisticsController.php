<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Services\Trips\TripService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldStatisticsController extends Controller
{
    public function index(Request $request): View
    {
        $queue = Trip::with(['boat', 'departurePort'])
            ->whereIn('status', ['عادت للميناء', 'بانتظار الإحصاء', 'تحت الإحصاء', 'بانتظار الاعتماد'])
            ->orderBy('return_time')
            ->get();

        $filtered = $request->filled('status')
            ? $queue->where('status', $request->query('status'))->values()
            : $queue;

        $stats = [
            'returned' => $queue->where('status', 'عادت للميناء')->count(),
            'pending' => $queue->where('status', 'بانتظار الإحصاء')->count(),
            'under' => $queue->where('status', 'تحت الإحصاء')->count(),
            'awaiting' => $queue->where('status', 'بانتظار الاعتماد')->count(),
            'declared' => $queue->sum('captain_input_kg'),
            'measured' => $queue->sum('actual_weight_kg'),
        ];

        return view('field-statistics.index', ['trips' => $filtered, 'stats' => $stats]);
    }

    /**
     * تسجيل الوزن الفعلي يمرّ بخدمة الرحلات نفسها التي يستعملها التطبيق: تُحدَّث
     * الحالة والفرق، ويُفتح مصيد الرحلة للبيع في دفتر مالكها.
     */
    public function record(Request $request, Trip $trip, TripService $trips): RedirectResponse
    {
        $data = $request->validate([
            'actual_weight_kg' => ['required', 'numeric', 'min:0'],
            'statistics_officer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $trips->count($trip, null, $request->user(), (float) $data['actual_weight_kg'], $data['notes'] ?? null);

        if (! empty($data['statistics_officer'])) {
            $trip->update(['statistics_officer' => $data['statistics_officer']]);
        }

        return redirect()->route('stats.field-statistics')->with('status', "تم تسجيل إحصاء الرحلة {$trip->trip_number}");
    }
}
