<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function index(Request $request): View
    {
        $regions = Region::orderBy('code')->get();
        $species = Species::orderByDesc('catch_kg')->get();

        $selectedRegion = $request->query('region');
        $filteredRegions = $selectedRegion
            ? $regions->where('name', $selectedRegion)->values()
            : $regions;

        $totalTons = $filteredRegions->sum('total_catch_tons');
        $totalBoats = $filteredRegions->sum('active_boats');
        $totalFishers = $filteredRegions->sum('active_fishers');

        $monthly = [
            ['m' => 'ينا', 'value' => 2120], ['m' => 'فبر', 'value' => 2240], ['m' => 'مار', 'value' => 2380],
            ['m' => 'أبر', 'value' => 2520], ['m' => 'ماي', 'value' => 2680], ['m' => 'يون', 'value' => 2410],
            ['m' => 'يول', 'value' => 2640], ['m' => 'أغس', 'value' => 2840],
        ];

        return view('production.index', [
            'regions' => $regions,
            'selectedRegion' => $selectedRegion,
            'period' => $request->query('period', 'الشهر'),
            'totalTons' => $totalTons,
            'totalBoats' => $totalBoats,
            'totalFishers' => $totalFishers,
            'avgPerBoat' => $totalBoats ? round($totalTons * 1000 / $totalBoats) : 0,
            'byRegion' => $filteredRegions->mapWithKeys(fn ($r) => [$r->name => (float) $r->total_catch_tons]),
            'bySpecies' => $species->take(10)->mapWithKeys(fn ($s) => [$s->name_ar => round($s->catch_kg / 1000)]),
            'monthly' => $monthly,
        ]);
    }
}