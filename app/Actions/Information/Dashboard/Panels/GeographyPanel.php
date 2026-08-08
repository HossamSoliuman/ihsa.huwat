<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Actions\Information\Dashboard\Support\QueueUrl;
use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Support\Collection;

final class GeographyPanel
{
    public function __construct(private QueueUrl $queueUrl) {}

    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        $useGovernorates = $scope->regionId !== null || $scope->governorateId !== null || $scope->portId !== null;
        $dimension = $useGovernorates ? 'governorates' : 'regions';
        $dimensionId = $dimension.'.id';
        $dimensionName = $dimension.'.name';

        $submissionRows = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->toBase()
            ->join((new Port)->getTable(), 'ports.id', '=', 'information_submissions.port_id')
            ->join((new Governorate)->getTable(), 'governorates.id', '=', 'ports.governorate_id')
            ->join((new Region)->getTable(), 'regions.id', '=', 'governorates.region_id')
            ->selectRaw("{$dimensionId} AS location_id, {$dimensionName} AS location_name, COUNT(*) AS aggregate")
            ->groupBy($dimensionId, $dimensionName)
            ->orderByDesc('aggregate')
            ->get();
        $marketRows = $scope->applyMarkets(FishMarket::query())
            ->toBase()
            ->join((new Governorate)->getTable(), 'governorates.id', '=', 'fish_markets.governorate_id')
            ->join((new Region)->getTable(), 'regions.id', '=', 'governorates.region_id')
            ->selectRaw("{$dimensionId} AS location_id, {$dimensionName} AS location_name, COUNT(*) AS aggregate")
            ->groupBy($dimensionId, $dimensionName)
            ->orderByDesc('aggregate')
            ->get();
        $brokerRows = FishMarketBroker::query()
            ->toBase()
            ->join((new FishMarket)->getTable(), 'fish_markets.id', '=', 'fish_market_brokers.fish_market_id')
            ->join((new Governorate)->getTable(), 'governorates.id', '=', 'fish_markets.governorate_id')
            ->join((new Region)->getTable(), 'regions.id', '=', 'governorates.region_id')
            ->when($scope->hasGeographyFilter(), fn ($query) => $query->whereIn('fish_markets.governorate_id', $scope->governorateIds()))
            ->selectRaw("{$dimensionId} AS location_id, {$dimensionName} AS location_name, COUNT(*) AS aggregate")
            ->groupBy($dimensionId, $dimensionName)
            ->orderByDesc('aggregate')
            ->get();

        return ['geography' => [
            'level' => $useGovernorates ? 'المحافظة' : 'المنطقة',
            'submissions' => $this->items($submissionRows, 'submissions', $scope, 'slot-1'),
            'markets' => $this->items($marketRows, 'markets', $scope, 'slot-2'),
            'brokers' => $this->items($brokerRows, 'brokers', $scope, 'slot-3'),
            'regionShare' => $this->regionShare($scope),
        ]];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function items(Collection $rows, string $registry, DashboardScope $scope, string $tone): array
    {
        return $rows->map(function (object $row) use ($registry, $scope, $tone): array {
            $location = $scope->regionId === null
                ? ['region_id' => (int) $row->location_id]
                : ['governorate_id' => (int) $row->location_id];
            $href = match ($registry) {
                'submissions' => $this->queueUrl->submissions(DashboardScope::fromFilters([...$scope->filters(), ...$location])),
                'markets' => $this->queueUrl->markets($scope, $location),
                default => null,
            };

            return [
                'label' => $row->location_name,
                'value' => (int) $row->aggregate,
                'tone' => $tone,
                'href' => $href,
            ];
        })->all();
    }

    /** @return list<array<string, mixed>> */
    private function regionShare(DashboardScope $scope): array
    {
        $regions = Region::query()
            ->when($scope->regionId, fn ($query, int $regionId) => $query->whereKey($regionId))
            ->ordered()
            ->get(['id', 'name']);
        $submissionCounts = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->toBase()
            ->join((new Port)->getTable(), 'ports.id', '=', 'information_submissions.port_id')
            ->join((new Governorate)->getTable(), 'governorates.id', '=', 'ports.governorate_id')
            ->selectRaw('governorates.region_id, COUNT(*) AS aggregate')
            ->groupBy('governorates.region_id')
            ->pluck('aggregate', 'governorates.region_id');
        $marketCounts = $scope->applyMarkets(FishMarket::query())
            ->toBase()
            ->join((new Governorate)->getTable(), 'governorates.id', '=', 'fish_markets.governorate_id')
            ->selectRaw('governorates.region_id, COUNT(*) AS aggregate')
            ->groupBy('governorates.region_id')
            ->pluck('aggregate', 'governorates.region_id');

        return [
            [
                'label' => 'الطلبات',
                'segments' => $regions->map(fn (Region $region, int $index): array => [
                    'label' => $region->name,
                    'value' => (int) $submissionCounts->get($region->id, 0),
                    'tone' => 'seq-'.(($index % 5) + 1),
                ])->all(),
            ],
            [
                'label' => 'الأسواق',
                'segments' => $regions->map(fn (Region $region, int $index): array => [
                    'label' => $region->name,
                    'value' => (int) $marketCounts->get($region->id, 0),
                    'tone' => 'seq-'.(($index % 5) + 1),
                ])->all(),
            ],
        ];
    }
}
