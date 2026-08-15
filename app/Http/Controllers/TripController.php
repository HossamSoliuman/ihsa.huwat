<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripController extends Controller
{
    public function index(Request $request): View
    {
        $trips = Trip::with(['boat', 'departurePort'])->orderByDesc('departure_time')->get();

        $filtered = $trips
            ->when($request->filled('search'), function ($c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($t) => str_contains(mb_strtolower($t->trip_number.' '.$t->captain_name.' '.$t->boat?->name), $q));
            })
            ->when($request->filled('port'), fn ($c) => $c->where('departure_port_id', (int) $request->query('port')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->values();

        $stats = [
            'total' => $trips->count(),
            'at_sea' => $trips->where('status', 'في البحر')->count(),
            'pending_stats' => $trips->whereIn('status', ['بانتظار الإحصاء', 'تحت الإحصاء'])->count(),
            'pending_approval' => $trips->where('status', 'بانتظار الاعتماد')->count(),
            'approved' => $trips->where('status', 'معتمدة')->count(),
            'approved_kg' => $trips->sum('approved_kg'),
        ];

        return view('trips.index', [
            'trips' => $filtered,
            'stats' => $stats,
            'ports' => Port::orderBy('name')->get(),
            'statuses' => ['مجدولة', 'بدأت', 'في البحر', 'عادت للميناء', 'بانتظار الإحصاء', 'تحت الإحصاء', 'بانتظار الاعتماد', 'معتمدة'],
        ]);
    }
}