<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Port;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class ApprovedCatchController extends Controller
{
    public function index(Request $request): View
    {
        $trips = Trip::with(['boat', 'departurePort', 'catchRecords.species'])
            ->whereIn('status', ['بانتظار الاعتماد', 'معتمدة'])
            ->orderByDesc('return_time')
            ->get();

        $filtered = $trips
            ->when($request->filled('port'), fn ($c) => $c->where('departure_port_id', (int) $request->query('port')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->values();

        $approved = $trips->where('status', 'معتمدة');

        $stats = [
            'awaiting' => $trips->where('status', 'بانتظار الاعتماد')->count(),
            'approved' => $approved->count(),
            'approved_kg' => $approved->sum('approved_kg'),
            'value' => $approved->flatMap->catchRecords->sum('total_value'),
            'avg' => $approved->count() ? round($approved->sum('approved_kg') / $approved->count()) : 0,
        ];

        return view('approved-catch.index', [
            'trips' => $filtered,
            'stats' => $stats,
            'ports' => Port::orderBy('name')->get(),
        ]);
    }

    public function approve(Trip $trip): RedirectResponse
    {
        $trip->update([
            'approved_kg' => $trip->actual_weight_kg ?? $trip->captain_input_kg,
            'status' => 'معتمدة',
        ]);

        AuditLog::create([
            'action' => 'اعتماد',
            'entity' => 'Trip',
            'record_label' => $trip->trip_number,
            'details' => "اعتماد مصيد الرحلة بمقدار {$trip->approved_kg} كجم",
        ]);

        return redirect()->route('approved-catch')->with('status', "تم اعتماد مصيد الرحلة {$trip->trip_number}");
    }
}