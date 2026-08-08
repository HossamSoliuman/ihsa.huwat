<?php

namespace App\Actions\Information\Dashboard\Support;

use App\Actions\Information\Dashboard\DashboardScope;
use Closure;
use Illuminate\Support\Facades\Cache;

final class DashboardCache
{
    private const VERSION = 'v3';

    public const REGISTRY = 'registry';

    public const REVIEW = 'review';

    public const GEOGRAPHY = 'geography';

    public const FLEET = 'fleet';

    public const MARKET = 'market';

    public const LICENCE = 'licence';

    public const COMPLETENESS = 'completeness';

    public const ATTENTION = 'attention';

    /** @var list<string> */
    public const PANELS = [
        self::REGISTRY,
        self::REVIEW,
        self::GEOGRAPHY,
        self::FLEET,
        self::MARKET,
        self::LICENCE,
        self::COMPLETENESS,
        self::ATTENTION,
    ];

    /**
     * @param  Closure(): array<string, mixed>  $build
     * @return array<string, mixed>
     */
    public function remember(string $panel, DashboardScope $scope, Closure $build): array
    {
        $cacheKey = 'information.dashboard.'.self::VERSION.".{$panel}.{$scope->fingerprint()}";
        $registryKey = $this->registryKey($panel);
        $knownKeys = Cache::get($registryKey, []);

        if (! in_array($cacheKey, $knownKeys, true)) {
            $knownKeys[] = $cacheKey;
            Cache::forever($registryKey, $knownKeys);
        }

        /** @var array<string, mixed> $result */
        $result = Cache::remember($cacheKey, now()->addMinutes(5), $build);

        return $result;
    }

    /** @param  list<string>  $panels */
    public static function forget(array $panels = self::PANELS): void
    {
        $cache = new self;

        foreach ($panels as $panel) {
            $registryKey = $cache->registryKey($panel);

            foreach (Cache::get($registryKey, []) as $cacheKey) {
                Cache::forget($cacheKey);
            }

            Cache::forget($registryKey);
        }
    }

    private function registryKey(string $panel): string
    {
        return 'information.dashboard.'.self::VERSION.".keys.{$panel}";
    }
}
