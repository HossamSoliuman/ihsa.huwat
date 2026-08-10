<?php

namespace App\Actions\Information\Dashboard;

use App\Actions\Information\Dashboard\Panels\AttentionPanel;
use App\Actions\Information\Dashboard\Panels\DataCompletenessPanel;
use App\Actions\Information\Dashboard\Panels\FleetPanel;
use App\Actions\Information\Dashboard\Panels\GeographyPanel;
use App\Actions\Information\Dashboard\Panels\LicenceExpiryPanel;
use App\Actions\Information\Dashboard\Panels\MarketPanel;
use App\Actions\Information\Dashboard\Panels\RegistryCensusPanel;
use App\Actions\Information\Dashboard\Panels\ReviewHealthPanel;
use App\Actions\Information\Dashboard\Support\DashboardCache;
use App\Actions\Information\Support\InformationScope;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;

final class BuildInformationDashboard
{
    public function __construct(
        private RegistryCensusPanel $registryCensus,
        private ReviewHealthPanel $reviewHealth,
        private GeographyPanel $geography,
        private FleetPanel $fleet,
        private MarketPanel $market,
        private LicenceExpiryPanel $licenceExpiry,
        private DataCompletenessPanel $dataCompleteness,
        private AttentionPanel $attention,
        private DashboardCache $cache,
    ) {}

    /**
     * @param  array{range?: string|null, region_id?: int|null, governorate_id?: int|null, port_id?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function execute(array $filters, ?InformationScope $account = null): array
    {
        $account ??= InformationScope::unrestricted();
        $scope = DashboardScope::fromFilters($filters, $account);

        return [
            'filters' => $scope->filters(),
            'rangeOptions' => [
                '7' => 'آخر 7 أيام',
                '30' => '30 يوماً',
                '90' => '90 يوماً',
                'year' => 'هذا العام',
                'all' => 'الكل',
            ],
            /** The pickers offer only what the account holds, so no filter reaches past it. */
            'regions' => $account->applyRegions(Region::query())->ordered()->get(['id', 'name']),
            'governorates' => $account->applyGovernorates(Governorate::query())->ordered()->get(['id', 'region_id', 'name']),
            'ports' => $account->applyPorts(Port::query())->ordered()->get(['id', 'governorate_id', 'name']),
            ...$this->cache->remember(DashboardCache::REGISTRY, $scope, fn (): array => $this->registryCensus->build($scope)),
            ...$this->cache->remember(DashboardCache::REVIEW, $scope, fn (): array => $this->reviewHealth->build($scope)),
            ...$this->cache->remember(DashboardCache::GEOGRAPHY, $scope, fn (): array => $this->geography->build($scope)),
            ...$this->cache->remember(DashboardCache::FLEET, $scope, fn (): array => $this->fleet->build($scope)),
            ...$this->cache->remember(DashboardCache::MARKET, $scope, fn (): array => $this->market->build($scope)),
            ...$this->cache->remember(DashboardCache::LICENCE, $scope, fn (): array => $this->licenceExpiry->build($scope)),
            ...$this->cache->remember(DashboardCache::COMPLETENESS, $scope, fn (): array => $this->dataCompleteness->build($scope)),
            ...$this->cache->remember(DashboardCache::ATTENTION, $scope, fn (): array => $this->attention->build($scope)),
            'generatedAt' => now()->locale('ar'),
        ];
    }
}
