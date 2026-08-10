<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Actions\Information\Dashboard\Support\QueueUrl;
use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketUnit;
use App\Models\FishMarketWorker;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use Illuminate\Database\Eloquent\Builder;

final class RegistryCensusPanel
{
    public function __construct(private QueueUrl $queueUrl) {}

    /** @return array{census: list<array<string, mixed>>} */
    public function build(DashboardScope $scope): array
    {
        $submissionCensus = $scope->applySubmissions(InformationSubmission::query(), null, null)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(DISTINCT CASE WHEN status = 'approved' THEN boat_id END) AS boats")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN crew_count ELSE 0 END), 0) AS crew")
            ->first();
        $currentCount = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)->count();
        $previousCount = $scope->previousStart === null
            ? 0
            : $scope->applySubmissions(InformationSubmission::query(), $scope->previousStart, $scope->previousEnd)->count();

        $marketQuery = $scope->applyMarkets(FishMarket::query());
        $marketCensus = (clone $marketQuery)->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active')
            ->first();

        $unitCounts = FishMarketUnit::query()
            ->toBase()
            ->join((new FishMarket)->getTable().' AS markets', 'markets.id', '=', 'fish_market_units.fish_market_id')
            ->tap(fn ($query) => $scope->applyJoinedMarkets($query))
            ->selectRaw('unit_type, COUNT(*) AS aggregate')
            ->groupBy('unit_type')
            ->pluck('aggregate', 'unit_type');
        $workerCount = FishMarketWorker::query()
            ->toBase()
            ->join((new FishMarketUnit)->getTable().' AS units', 'units.id', '=', 'fish_market_workers.fish_market_unit_id')
            ->join((new FishMarket)->getTable().' AS markets', 'markets.id', '=', 'units.fish_market_id')
            ->tap(fn ($query) => $scope->applyJoinedMarkets($query))
            ->count();
        $brokerCounts = FishMarketBroker::query()
            ->toBase()
            ->join((new FishMarket)->getTable().' AS markets', 'markets.id', '=', 'fish_market_brokers.fish_market_id')
            ->tap(fn ($query) => $scope->applyJoinedMarkets($query))
            ->selectRaw('entity_type, COUNT(*) AS aggregate')
            ->groupBy('entity_type')
            ->pluck('aggregate', 'entity_type');

        $activeGovernorates = $scope->applyAccountGovernorates(Governorate::query()->selectable())
            ->when($scope->regionId, fn (Builder $query, int $regionId): Builder => $query->where('region_id', $regionId))
            ->when($scope->governorateId, fn (Builder $query, int $governorateId): Builder => $query->whereKey($governorateId))
            ->pluck('id');
        $submissionGovernorates = InformationSubmission::query()
            ->join('ports', 'ports.id', '=', 'information_submissions.port_id')
            ->whereIn('ports.governorate_id', $activeGovernorates)
            ->distinct()
            ->pluck('ports.governorate_id');
        $marketGovernorates = FishMarket::query()
            ->whereIn('governorate_id', $activeGovernorates)
            ->distinct()
            ->pluck('governorate_id');
        $coveredGovernorates = $submissionGovernorates->merge($marketGovernorates)->unique()->count();

        $shops = (int) $unitCounts->get(FishMarketUnit::TYPE_SHOP, 0);
        $stalls = (int) $unitCounts->get(FishMarketUnit::TYPE_AUCTION_STALL, 0);
        $individuals = (int) $brokerCounts->get(FishMarketBroker::TYPE_INDIVIDUAL, 0);
        $establishments = (int) $brokerCounts->get(FishMarketBroker::TYPE_ESTABLISHMENT, 0);

        return ['census' => [
            $this->tile('الطلبات', (int) $submissionCensus->total, 'inbox', $this->queueUrl->submissions($scope), $this->relativeDelta($currentCount, $previousCount)),
            $this->tile('المراكب المسجّلة', (int) $submissionCensus->boats, 'boat'),
            $this->tile('البحارة', (int) $submissionCensus->crew, 'users'),
            $this->tile('أسواق السمك', (int) $marketCensus->total, 'market', $this->queueUrl->markets($scope), suffix: (int) $marketCensus->active.' نشط'),
            $this->tile('محلات ودكات', $shops + $stalls, 'grid', $this->queueUrl->markets($scope), suffix: $shops.' محل · '.$stalls.' دكة'),
            $this->tile('عمالة الأسواق', $workerCount, 'users'),
            $this->tile('الدلالين', $individuals + $establishments, 'broker', $this->queueUrl->brokers($scope), suffix: $individuals.' فرد · '.$establishments.' منشأة'),
            $this->tile('التغطية', $coveredGovernorates, 'map', suffix: 'من '.$activeGovernorates->count().' محافظة'),
        ]];
    }

    /** @return array<string, mixed> */
    private function tile(string $label, int $value, string $icon, ?string $href = null, ?float $delta = null, ?string $suffix = null): array
    {
        return compact('label', 'value', 'icon', 'href', 'delta', 'suffix') + [
            'tone' => null,
            'delta_good' => true,
            'delta_suffix' => '%',
        ];
    }

    private function relativeDelta(int $current, int $previous): ?float
    {
        return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null;
    }
}
