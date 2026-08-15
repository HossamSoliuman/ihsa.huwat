<?php

namespace App\Http\Controllers;

use App\Models\FishingSite;
use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FishingSiteController extends Controller
{
    public function index(Request $request): View
    {
        $sites = FishingSite::with('port.governorate')->orderByDesc('catch_kg')->get();

        $filtered = $sites
            ->when($request->filled('search'), function ($c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($s) => str_contains(mb_strtolower($s->name), $q));
            })
            ->when($request->filled('port'), fn ($c) => $c->where('port_id', (int) $request->query('port')))
            ->when($request->filled('pressure'), fn ($c) => $c->where('pressure_level', $request->query('pressure')))
            ->values();

        $stats = [
            'total' => $sites->count(),
            'normal' => $sites->where('pressure_level', 'طبيعي')->count(),
            'watch' => $sites->where('pressure_level', 'مراقبة')->count(),
            'high' => $sites->where('pressure_level', 'ضغط مرتفع')->count(),
            'alarm' => $sites->where('pressure_level', 'إنذار')->count(),
            'catch' => $sites->sum('catch_kg'),
        ];

        return view('fishing-sites.index', [
            'sites' => $filtered,
            'stats' => $stats,
            'ports' => Port::orderBy('name')->get(),
        ]);
    }
}