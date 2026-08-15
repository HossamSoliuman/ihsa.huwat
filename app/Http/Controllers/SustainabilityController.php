<?php

namespace App\Http\Controllers;

use App\Models\BycatchRecord;
use App\Models\FishingSite;
use App\Models\Species;
use Illuminate\View\View;

class SustainabilityController extends Controller
{
    public function index(): View
    {
        $species = Species::orderByDesc('catch_kg')->get();
        $sites = FishingSite::all();

        $statusCounts = collect(['مستقر', 'مراقبة', 'ضغط صيد مرتفع', 'انخفاض حاد'])
            ->mapWithKeys(fn ($s) => [$s => $species->where('status', $s)->count()]);

        $stats = [
            'species' => $species->count(),
            'stable' => $statusCounts['مستقر'],
            'pressure' => $statusCounts['ضغط صيد مرتفع'] + $statusCounts['انخفاض حاد'],
            'sites_risk' => $sites->whereIn('pressure_level', ['ضغط مرتفع', 'إنذار'])->count(),
            'bycatch' => BycatchRecord::sum('quantity_kg'),
            'released' => BycatchRecord::where('action_taken', 'إعادة للبحر')->sum('quantity_kg'),
        ];

        return view('sustainability.index', [
            'stats' => $stats,
            'statusCounts' => $statusCounts,
            'watchlist' => $species->whereIn('status', ['ضغط صيد مرتفع', 'انخفاض حاد', 'مراقبة'])->take(15),
            'topSites' => $sites->sortByDesc('catch_kg')->take(8),
        ]);
    }
}