<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoatController extends Controller
{
    public function index(Request $request): View
    {
        $boats = Boat::with('port.governorate.region')->orderBy('name')->get();

        $filtered = $boats
            ->when($request->filled('search'), function ($c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($b) => str_contains(mb_strtolower($b->name.' '.$b->boat_number.' '.$b->owner.' '.$b->captain), $q));
            })
            ->when($request->filled('port'), fn ($c) => $c->where('port_id', (int) $request->query('port')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->when($request->filled('license'), fn ($c) => $c->where('license_status', $request->query('license')))
            ->values();

        $stats = [
            'total' => $boats->count(),
            'active' => $boats->where('status', 'نشط')->count(),
            'at_sea' => $boats->where('status', 'في البحر')->count(),
            'expiring' => $boats->whereIn('license_status', ['قريبة الانتهاء', 'منتهية'])->count(),
            'catch' => $boats->sum('total_catch_kg'),
            'violations' => $boats->sum('violations_count'),
        ];

        return view('boats.index', [
            'boats' => $filtered,
            'stats' => $stats,
            'ports' => Port::orderBy('name')->get(),
        ]);
    }
}