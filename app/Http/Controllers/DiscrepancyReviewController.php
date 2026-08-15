<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscrepancyReviewController extends Controller
{
    public function index(Request $request): View
    {
        $trips = Trip::with(['boat', 'departurePort'])
            ->whereNotNull('diff_kg')
            ->where('diff_kg', '!=', 0)
            ->orderByDesc('return_time')
            ->get();

        $threshold = (float) $request->query('threshold', 0);
        $filtered = $trips->filter(fn ($t) => abs((float) $t->diff_kg) >= $threshold)->values();

        $stats = [
            'total' => $trips->count(),
            'high' => $trips->filter(fn ($t) => abs((float) $t->diff_kg) >= 50)->count(),
            'pending' => $trips->where('status', 'بانتظار الاعتماد')->count(),
            'net' => $trips->sum('diff_kg'),
            'avg' => $trips->count() ? round($trips->avg(fn ($t) => abs((float) $t->diff_kg)), 1) : 0,
        ];

        return view('discrepancy-review.index', [
            'trips' => $filtered,
            'stats' => $stats,
            'threshold' => $threshold,
        ]);
    }

    public function resolve(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate([
            'approved_kg' => ['required', 'numeric', 'min:0'],
            'notes' => ['required', 'string'],
        ]);

        $trip->update(['approved_kg' => $data['approved_kg'], 'notes' => $data['notes'], 'status' => 'معتمدة']);

        AuditLog::create([
            'action' => 'معالجة فرق',
            'entity' => 'Trip',
            'record_label' => $trip->trip_number,
            'details' => "اعتماد {$data['approved_kg']} كجم بعد مراجعة الفرق: {$data['notes']}",
        ]);

        return redirect()->route('discrepancy-review')->with('status', "تمت معالجة فرق الرحلة {$trip->trip_number}");
    }
}