<?php

namespace App\Http\Controllers;

use App\Models\MarketAuction;
use App\Models\Region;
use App\Models\Trip;
use Illuminate\View\View;

class FoodSecurityController extends Controller
{
    public const POPULATION = 32175224;

    public function index(): View
    {
        $regions = Region::orderByDesc('total_catch_tons')->get();
        $approvedKg = (float) Trip::where('status', 'معتمدة')->sum('approved_kg');
        $totalTons = (float) $regions->sum('total_catch_tons');
        $avgPrice = round((float) MarketAuction::avg('avg_price_per_kg'), 2);

        return view('food-security.index', [
            'totalTons' => $totalTons,
            'approvedKg' => $approvedKg,
            'perCapitaKg' => round($totalTons * 1000 / self::POPULATION, 2),
            'population' => self::POPULATION,
            'avgPrice' => $avgPrice,
            'estimatedValue' => round($totalTons * 1000 * $avgPrice),
            'selfSufficiency' => round(min(100, $totalTons * 1000 / self::POPULATION / 12 * 100), 1),
            'byRegion' => $regions->mapWithKeys(fn ($r) => [$r->name => (float) $r->total_catch_tons]),
            'regions' => $regions,
        ]);
    }
}