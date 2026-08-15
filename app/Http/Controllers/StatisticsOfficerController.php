<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\StatisticsOfficer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticsOfficerController extends Controller
{
    public function index(Request $request): View
    {
        $officers = StatisticsOfficer::with('port')->orderByDesc('trips_counted')->get();

        $filtered = $officers
            ->when($request->filled('port'), fn ($c) => $c->where('port_id', (int) $request->query('port')))
            ->when($request->filled('shift'), fn ($c) => $c->where('shift', $request->query('shift')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->values();

        $stats = [
            'total' => $officers->count(),
            'active' => $officers->where('status', 'نشط')->count(),
            'trips' => $officers->sum('trips_counted'),
            'avg' => $officers->count() ? round($officers->sum('trips_counted') / $officers->count()) : 0,
            'ports' => $officers->pluck('port_id')->unique()->count(),
        ];

        return view('statistics-officers.index', [
            'officers' => $filtered,
            'stats' => $stats,
            'ports' => Port::orderBy('name')->get(),
            'shifts' => $officers->pluck('shift')->unique()->filter()->values(),
        ]);
    }
}