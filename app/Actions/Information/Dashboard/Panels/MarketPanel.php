<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Actions\Information\Dashboard\Support\QueueUrl;
use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketUnit;

final class MarketPanel
{
    public function __construct(private QueueUrl $queueUrl) {}

    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        $unitsPerMarket = $scope->applyMarkets(FishMarket::query())
            ->withCount('units')
            ->orderByDesc('units_count')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name'])
            ->map(fn (FishMarket $market): array => [
                'label' => $market->name,
                'value' => $market->units_count,
                'tone' => 'slot-1',
                'href' => route('information.admin.markets.show', $market),
            ])->all();
        $workersByUnitType = FishMarketUnit::query()
            ->toBase()
            ->join((new FishMarket)->getTable().' AS markets', 'markets.id', '=', 'fish_market_units.fish_market_id')
            ->leftJoin('fish_market_workers AS workers', 'workers.fish_market_unit_id', '=', 'fish_market_units.id')
            ->tap(fn ($query) => $scope->applyJoinedMarkets($query))
            ->selectRaw('unit_type, COUNT(workers.id) AS aggregate')
            ->groupBy('unit_type')
            ->pluck('aggregate', 'unit_type');
        $brokerMix = FishMarketBroker::query()
            ->toBase()
            ->join((new FishMarket)->getTable().' AS markets', 'markets.id', '=', 'fish_market_brokers.fish_market_id')
            ->tap(fn ($query) => $scope->applyJoinedMarkets($query))
            ->selectRaw('entity_type, COUNT(*) AS aggregate')
            ->groupBy('entity_type')
            ->pluck('aggregate', 'entity_type');
        $markets = $scope->applyMarkets(FishMarket::query());

        return ['marketAnalysis' => [
            'unitsPerMarket' => $unitsPerMarket,
            'workersByUnitType' => [
                ['label' => 'عمالة المحلات', 'value' => (int) $workersByUnitType->get(FishMarketUnit::TYPE_SHOP, 0), 'tone' => 'slot-2'],
                ['label' => 'عمالة الدكات', 'value' => (int) $workersByUnitType->get(FishMarketUnit::TYPE_AUCTION_STALL, 0), 'tone' => 'slot-4'],
            ],
            'brokerMix' => [
                ['label' => 'أفراد', 'value' => (int) $brokerMix->get(FishMarketBroker::TYPE_INDIVIDUAL, 0), 'tone' => 'slot-1'],
                ['label' => 'منشآت', 'value' => (int) $brokerMix->get(FishMarketBroker::TYPE_ESTABLISHMENT, 0), 'tone' => 'slot-3'],
            ],
            'coverage' => [
                'without_units' => (clone $markets)->whereDoesntHave('units')->count(),
                'without_brokers' => (clone $markets)->whereDoesntHave('brokers')->count(),
                'href' => $this->queueUrl->markets($scope),
            ],
        ]];
    }
}
