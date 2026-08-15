<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\MarketAuction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketController extends Controller
{
    public function index(Request $request): View
    {
        $markets = Market::withCount('auctions')->orderBy('name')->get();
        $auctions = MarketAuction::with(['market', 'species'])->orderByDesc('auction_date')->get();

        $filtered = $markets
            ->when($request->filled('region'), fn ($c) => $c->where('region', $request->query('region')))
            ->when($request->filled('type'), fn ($c) => $c->where('market_type', $request->query('type')))
            ->values();

        $stats = [
            'markets' => $markets->count(),
            'shops' => $markets->sum('fish_shops_count'),
            'stalls' => $markets->sum('auction_stalls_count'),
            'offered' => $auctions->sum('quantity_offered_kg'),
            'sold' => $auctions->sum('quantity_sold_kg'),
            'avg_price' => round((float) $auctions->avg('avg_price_per_kg'), 2),
        ];

        return view('markets.index', [
            'markets' => $filtered,
            'auctions' => $auctions->take(20),
            'stats' => $stats,
            'regions' => $markets->pluck('region')->unique()->filter()->values(),
            'types' => $markets->pluck('market_type')->unique()->filter()->values(),
            'priceBySpecies' => $auctions->groupBy(fn ($a) => $a->species?->name_ar ?? 'غير محدد')
                ->map(fn ($g) => round((float) $g->avg('avg_price_per_kg'), 2))
                ->sortDesc()
                ->take(10),
        ]);
    }
}