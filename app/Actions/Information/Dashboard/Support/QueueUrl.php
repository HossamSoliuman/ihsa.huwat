<?php

namespace App\Actions\Information\Dashboard\Support;

use App\Actions\Information\Dashboard\DashboardScope;

final class QueueUrl
{
    /** @param  array<string, string>  $extra */
    public function submissions(DashboardScope $scope, ?string $status = null, array $extra = []): string
    {
        return route('information.admin.index', $this->parameters($scope, [
            'status' => $status,
            ...$extra,
        ]));
    }

    /** @param  array<string, mixed>  $extra */
    public function markets(DashboardScope $scope, array $extra = []): string
    {
        return route('information.admin.markets.index', $this->parameters($scope, $extra, includePort: false));
    }

    /** @param  array<string, mixed>  $extra */
    public function brokers(DashboardScope $scope, array $extra = []): string
    {
        return route('information.admin.brokers.index', array_filter(
            $extra,
            fn (mixed $value): bool => $value !== null && $value !== '',
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function parameters(DashboardScope $scope, array $extra, bool $includePort = true): array
    {
        return array_filter([
            'range' => $scope->range,
            'region_id' => $scope->regionId,
            'governorate_id' => $scope->governorateId,
            'port_id' => $includePort ? $scope->portId : null,
            ...$extra,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
