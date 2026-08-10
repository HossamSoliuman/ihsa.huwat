<?php

namespace App\Actions\Information\Dashboard;

use App\Actions\Information\Support\InformationScope;
use App\Models\FishMarket;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class DashboardScope
{
    /** @var Collection<int, int>|null */
    private ?Collection $resolvedGovernorateIds = null;

    /** @var Collection<int, int>|null */
    private ?Collection $resolvedPortIds = null;

    private function __construct(
        public readonly string $range,
        public readonly ?int $regionId,
        public readonly ?int $governorateId,
        public readonly ?int $portId,
        public readonly ?Carbon $currentStart,
        public readonly Carbon $currentEnd,
        public readonly ?Carbon $previousStart,
        public readonly ?Carbon $previousEnd,
        /** What the account signed in may see at all. The filters narrow within it, never past it. */
        private readonly InformationScope $account,
    ) {}

    /**
     * @param  array{range?: string|null, region_id?: int|null, governorate_id?: int|null, port_id?: int|null}  $filters
     */
    public static function fromFilters(array $filters, ?InformationScope $account = null): self
    {
        $range = $filters['range'] ?? '30';
        $currentEnd = now();
        $account ??= InformationScope::unrestricted();

        if ($range === 'all') {
            return new self(
                range: $range,
                regionId: isset($filters['region_id']) ? (int) $filters['region_id'] : null,
                governorateId: isset($filters['governorate_id']) ? (int) $filters['governorate_id'] : null,
                portId: isset($filters['port_id']) ? (int) $filters['port_id'] : null,
                currentStart: null,
                currentEnd: $currentEnd,
                previousStart: null,
                previousEnd: null,
                account: $account,
            );
        }

        $currentStart = $range === 'year'
            ? now()->startOfYear()
            : today()->subDays(((int) $range) - 1);
        $periodSeconds = $currentStart->diffInSeconds($currentEnd) + 1;
        $previousEnd = $currentStart->copy()->subSecond();

        return new self(
            range: $range,
            regionId: isset($filters['region_id']) ? (int) $filters['region_id'] : null,
            governorateId: isset($filters['governorate_id']) ? (int) $filters['governorate_id'] : null,
            portId: isset($filters['port_id']) ? (int) $filters['port_id'] : null,
            currentStart: $currentStart,
            currentEnd: $currentEnd,
            previousStart: $previousEnd->copy()->subSeconds($periodSeconds - 1),
            previousEnd: $previousEnd,
            account: $account,
        );
    }

    /** @return array{range: string, region_id: int|null, governorate_id: int|null, port_id: int|null} */
    public function filters(): array
    {
        return [
            'range' => $this->range,
            'region_id' => $this->regionId,
            'governorate_id' => $this->governorateId,
            'port_id' => $this->portId,
        ];
    }

    /**
     * Two accounts holding different ports ask the same filters of a different dashboard, so
     * what the account may see belongs in the cache key alongside the filters themselves.
     */
    public function fingerprint(): string
    {
        return sha1((string) json_encode([
            ...$this->filters(),
            'account' => [$this->account->level, $this->account->ids],
        ]));
    }

    public function hasGeographyFilter(): bool
    {
        return ! $this->account->isUnrestricted()
            || $this->regionId !== null
            || $this->governorateId !== null
            || $this->portId !== null;
    }

    /** @return Collection<int, int> */
    public function governorateIds(): Collection
    {
        if ($this->resolvedGovernorateIds !== null) {
            return $this->resolvedGovernorateIds;
        }

        $query = $this->account->applyGovernorates(Governorate::query()->select('governorates.id'));

        if ($this->portId !== null) {
            $query->whereIn('governorates.id', Port::query()->whereKey($this->portId)->select('governorate_id'));
        }

        if ($this->governorateId !== null) {
            $query->whereKey($this->governorateId);
        }

        if ($this->regionId !== null) {
            $query->where('region_id', $this->regionId);
        }

        $ids = $query->pluck('id')->map(fn (mixed $id): int => (int) $id)->values();

        return $this->resolvedGovernorateIds = $this->hasGeographyFilter() && $ids->isEmpty()
            ? collect([0])
            : $ids;
    }

    /** @return Collection<int, int> */
    public function portIds(): Collection
    {
        if ($this->resolvedPortIds !== null) {
            return $this->resolvedPortIds;
        }

        $query = $this->account->applyPorts(Port::query()->select('ports.id'));

        if ($this->portId !== null) {
            $query->whereKey($this->portId);
        }

        if ($this->governorateId !== null || $this->regionId !== null) {
            $query->whereIn('governorate_id', $this->governorateIds());
        }

        $ids = $query->pluck('id')->map(fn (mixed $id): int => (int) $id)->values();

        return $this->resolvedPortIds = $this->hasGeographyFilter() && $ids->isEmpty()
            ? collect([0])
            : $ids;
    }

    /** @param  Builder<InformationSubmission>  $query */
    public function applySubmissions(Builder $query, ?Carbon $start, ?Carbon $end): Builder
    {
        return $query
            ->when($this->hasGeographyFilter(), fn (Builder $query): Builder => $query->whereIn('port_id', $this->portIds()))
            ->when($start, fn (Builder $query, Carbon $date): Builder => $query->where('submitted_at', '>=', $date))
            ->when($end, fn (Builder $query, Carbon $date): Builder => $query->where('submitted_at', '<=', $date));
    }

    /**
     * The account narrows to the markets it holds — which for a port moderator is none —
     * and the filters narrow the geography within that.
     *
     * @param  Builder<FishMarket>  $query
     */
    public function applyMarkets(Builder $query): Builder
    {
        return $this->account->applyMarkets($query)->when(
            $this->hasGeographyFilter(),
            fn (Builder $query): Builder => $query->whereIn('governorate_id', $this->governorateIds()),
        );
    }

    /**
     * The geography a panel may name at all. Unlike the readers above these carry no filter
     * — the callers apply their own — and only hold the query to what the account reaches.
     *
     * @param  Builder<Governorate>  $query
     */
    public function applyAccountGovernorates(Builder $query): Builder
    {
        return $this->account->applyGovernorates($query);
    }

    /** @param  Builder<Region>  $query */
    public function applyAccountRegions(Builder $query): Builder
    {
        return $this->account->applyRegions($query);
    }

    /**
     * The same narrowing for the panels that reach markets through a join and count their
     * units, workers or brokers rather than the markets themselves. The filters narrow by
     * governorate as they always have; an account holding named markets narrows to those on
     * top of it, so a moderator never counts the market next door.
     *
     * @template TQuery of Builder|\Illuminate\Database\Query\Builder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public function applyJoinedMarkets($query, string $alias = 'markets')
    {
        return $query
            ->when($this->hasGeographyFilter(), fn ($query) => $query->whereIn($alias.'.governorate_id', $this->governorateIds()))
            ->when(! $this->account->isUnrestricted(), fn ($query) => $query->whereIn($alias.'.id', $this->account->marketIds()));
    }
}
