<?php

namespace App\Http\Controllers;

use App\Models\MarketAuction;
use App\Models\Trip;
use Illuminate\View\View;

class SupplyChainController extends Controller
{
    public function index(): View
    {
        $trips = Trip::all();
        $auctions = MarketAuction::with('market')->get();

        $approved = (float) $trips->where('status', 'معتمدة')->sum('approved_kg');
        $offered = (float) $auctions->sum('quantity_offered_kg');
        $sold = (float) $auctions->sum('quantity_sold_kg');

        $stages = [
            ['label' => 'المصيد المعتمد', 'value' => $approved, 'icon' => 'badge-check', 'tone' => 'success'],
            ['label' => 'وصل للأسواق', 'value' => $offered, 'icon' => 'hammer', 'tone' => 'primary'],
            ['label' => 'تم بيعه', 'value' => $sold, 'icon' => 'check-circle', 'tone' => 'info'],
            ['label' => 'فاقد أو مخزون', 'value' => max(0, $offered - $sold), 'icon' => 'alert-triangle', 'tone' => 'warning'],
        ];

        return view('supply-chain.index', [
            'stages' => $stages,
            'approved' => $approved,
            'offered' => $offered,
            'sold' => $sold,
            'marketShare' => $auctions->groupBy(fn ($a) => $a->market?->name ?? 'غير محدد')
                ->map(fn ($g) => round((float) $g->sum('quantity_sold_kg')))
                ->sortDesc()
                ->take(8),
            'toMarketRate' => $approved > 0 ? round($offered / $approved * 100, 1) : 0,
            'sellRate' => $offered > 0 ? round($sold / $offered * 100, 1) : 0,
        ]);
    }
}