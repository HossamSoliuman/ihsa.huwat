<?php

namespace App\Actions\Information\Support;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Region;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * What one account may reach inside the information centre. The desk reaches everything;
 * a moderator reaches the records named on its `user_scopes` rows and nothing else, and
 * the level those rows carry also decides which sections of the centre it opens at all.
 *
 * Every `*Ids()` reader resolves downwards — a region names its governorates, which name
 * their ports and markets — and returns an empty list for an unrestricted scope, so read
 * them only past `isUnrestricted()` or through the `apply*` methods that check for you.
 */
final class InformationScope
{
    public const SUBMISSIONS = 'submissions';

    public const PORTS = 'ports';

    public const MARKETS = 'markets';

    public const BROKERS = 'brokers';

    public const SETTINGS = 'settings';

    public const MODERATORS = 'moderators';

    /** Ruling on a submission, as against merely reading it. */
    public const REVIEW = 'review';

    /**
     * Which sections each level answers for. A port answers for the boats homed there and
     * the people who filed under it; a market answers for its units and its دلالين. A
     * governorate or a region answers for everything inside it. الإعدادات and the moderator
     * accounts appear on no list — they stay with the desk.
     *
     * @var array<string, list<string>>
     */
    private const SECTIONS = [
        UserScope::TYPE_REGION => [self::SUBMISSIONS, self::PORTS, self::MARKETS, self::BROKERS],
        UserScope::TYPE_GOVERNORATE => [self::SUBMISSIONS, self::PORTS, self::MARKETS, self::BROKERS],
        UserScope::TYPE_PORT => [self::SUBMISSIONS, self::PORTS],
        UserScope::TYPE_MARKET => [self::MARKETS, self::BROKERS],
    ];

    /** @var array<string, list<int>> */
    private array $resolved = [];

    /** @param  list<int>  $ids */
    private function __construct(
        private readonly bool $unrestricted,
        public readonly ?string $level,
        public readonly array $ids,
    ) {}

    public static function unrestricted(): self
    {
        return new self(true, null, []);
    }

    /**
     * Anything that is not desk staff is read as a moderator, so a role that slips past the
     * route gate still arrives holding nothing rather than holding everything.
     */
    public static function for(User $user): self
    {
        $role = $user->relationLoaded('role') ? $user->role : $user->role()->first();

        if ($role !== null && in_array($role->code, config('information.desk_roles'), true)) {
            return self::unrestricted();
        }

        /** Read as a query rather than as a relation: lazy loading is off outside production. */
        $rows = $user->assignedScopes()->orderBy('id')->get(['scope_type', 'scope_id']);
        $level = $rows->first()?->scope_type;

        return new self(
            false,
            $level,
            $rows->where('scope_type', $level)->pluck('scope_id')->map(intval(...))->values()->all(),
        );
    }

    public function isUnrestricted(): bool
    {
        return $this->unrestricted;
    }

    /** A moderator whose assignments were all removed reaches nothing at all. */
    public function allows(string $section): bool
    {
        return $this->unrestricted || in_array($section, self::SECTIONS[$this->level] ?? [], true);
    }

    /**
     * A region or a governorate holds whatever grows inside it, so a market opened there is
     * in scope the moment it is saved. An account pinned to a fixed list of markets would
     * only lose sight of a market it opened, so it manages the ones it holds and adds none.
     */
    public function allowsNewMarkets(): bool
    {
        return $this->unrestricted
            || in_array($this->level, [UserScope::TYPE_REGION, UserScope::TYPE_GOVERNORATE], true);
    }

    public function allowsPort(?int $portId): bool
    {
        return $this->unrestricted || ($portId !== null && in_array($portId, $this->portIds(), true));
    }

    public function allowsMarket(?int $marketId): bool
    {
        return $this->unrestricted || ($marketId !== null && in_array($marketId, $this->marketIds(), true));
    }

    /** @return list<int> */
    public function regionIds(): array
    {
        return $this->resolved['regions'] ??= match ($this->level) {
            UserScope::TYPE_REGION => $this->ids,
            UserScope::TYPE_GOVERNORATE, UserScope::TYPE_PORT, UserScope::TYPE_MARKET => $this->identifiers(
                Governorate::query()->whereKey($this->governorateIds()),
                'region_id',
            ),
            default => [],
        };
    }

    /** @return list<int> */
    public function governorateIds(): array
    {
        return $this->resolved['governorates'] ??= match ($this->level) {
            UserScope::TYPE_REGION => $this->identifiers(Governorate::query()->whereIn('region_id', $this->ids)),
            UserScope::TYPE_GOVERNORATE => $this->ids,
            UserScope::TYPE_PORT => $this->identifiers(Port::query()->whereKey($this->ids), 'governorate_id'),
            UserScope::TYPE_MARKET => $this->identifiers(FishMarket::query()->whereKey($this->ids), 'governorate_id'),
            default => [],
        };
    }

    /** @return list<int> */
    public function portIds(): array
    {
        return $this->resolved['ports'] ??= match ($this->level) {
            UserScope::TYPE_PORT => $this->ids,
            UserScope::TYPE_REGION, UserScope::TYPE_GOVERNORATE => $this->identifiers(
                Port::query()->whereIn('governorate_id', $this->governorateIds()),
            ),
            default => [],
        };
    }

    /** @return list<int> */
    public function marketIds(): array
    {
        return $this->resolved['markets'] ??= match ($this->level) {
            UserScope::TYPE_MARKET => $this->ids,
            UserScope::TYPE_REGION, UserScope::TYPE_GOVERNORATE => $this->identifiers(
                FishMarket::query()->whereIn('governorate_id', $this->governorateIds()),
            ),
            default => [],
        };
    }

    /** @param  Builder<Port>  $query */
    public function applyPorts(Builder $query): Builder
    {
        return $this->unrestricted ? $query : $query->whereIn('ports.id', $this->portIds());
    }

    /** @param  Builder<FishMarket>  $query */
    public function applyMarkets(Builder $query): Builder
    {
        return $this->unrestricted ? $query : $query->whereIn('fish_markets.id', $this->marketIds());
    }

    /** @param  Builder<FishMarketBroker>  $query */
    public function applyBrokers(Builder $query): Builder
    {
        return $this->unrestricted ? $query : $query->whereIn('fish_market_id', $this->marketIds());
    }

    /** @param  Builder<InformationSubmission>  $query */
    public function applySubmissions(Builder $query): Builder
    {
        return $this->unrestricted ? $query : $query->whereIn('port_id', $this->portIds());
    }

    /** @param  Builder<Governorate>  $query */
    public function applyGovernorates(Builder $query): Builder
    {
        return $this->unrestricted ? $query : $query->whereIn('governorates.id', $this->governorateIds());
    }

    /** @param  Builder<Region>  $query */
    public function applyRegions(Builder $query): Builder
    {
        return $this->unrestricted ? $query : $query->whereIn('regions.id', $this->regionIds());
    }

    /**
     * @param  Builder<Model>  $query
     * @return list<int>
     */
    private function identifiers(Builder $query, string $column = 'id'): array
    {
        return $query->pluck($column)->map(intval(...))->unique()->values()->all();
    }
}
