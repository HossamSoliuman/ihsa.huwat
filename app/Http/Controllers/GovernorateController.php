<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovernorateController extends Controller
{
    public function index(Request $request): View
    {
        $governorates = Governorate::with('region')->get();

        $filtered = $governorates
            ->when($request->filled('search'), fn ($c) => $c->filter(fn ($g) => str_contains($g->name, $request->query('search'))))
            ->when($request->filled('region'), fn ($c) => $c->filter(fn ($g) => $g->region?->name === $request->query('region')))
            ->values();

        $stats = [
            'total' => $governorates->count(),
            'ports' => $governorates->sum('ports_count'),
            'boats' => $governorates->sum('active_boats'),
            'fishers' => $governorates->sum('active_fishers'),
            'catch' => $governorates->sum('total_catch_tons'),
        ];

        return view('governorates.index', [
            'governorates' => $filtered,
            'stats' => $stats,
            'regions' => Region::orderBy('code')->pluck('name'),
        ]);
    }

    public function show(Governorate $governorate): View
    {
        $governorate->load('region');
        $ports = $governorate->ports;
        $portIds = $ports->pluck('id');
        $boats = Boat::whereIn('port_id', $portIds)->get();

        $catches = CatchRecord::whereHas('trip', fn ($q) => $q->whereIn('departure_port_id', $portIds))
            ->orderByDesc('recorded_at')
            ->get();

        $trend = $catches->groupBy(fn ($c) => $c->recorded_at->toDateString())
            ->map(fn ($g) => round($g->sum('quantity_kg')))
            ->sortKeys()
            ->take(-7);

        $statusCounts = $boats->countBy('status');
        $activeBoats = $boats->whereIn('status', ['نشط', 'في البحر'])->count();
        $todayCatch = (int) ($trend[now()->toDateString()] ?? 0);

        return view('governorates.show', compact('governorate', 'ports', 'boats', 'trend', 'statusCounts', 'activeBoats', 'todayCatch'));
    }
}