<?php

namespace App\Actions\Government;

use App\Models\Region;
use App\Models\Season;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BuildSeasonIndexAction
{
    /**
     * @param  array{from?: string|null, to?: string|null, status?: string|null, region_id?: int|null, search?: string|null}  $filters
     * @return array{
     *     seasons: LengthAwarePaginator<int, Season>,
     *     summary: object,
     *     regions: Collection<int, Region>,
     *     statuses: array<string, string>
     * }
     */
    public function handle(array $filters): array
    {
        $seasons = Season::query()
            ->select([
                'id', 'region_id', 'name', 'status', 'start_date', 'end_date',
                'licenses_count', 'fishing_tools', 'minimum_size', 'maximum_size',
            ])
            ->with('region:id,name')
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('end_date', '<=', $date))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['region_id'] ?? null, fn (Builder $query, int $regionId): Builder => $query->where('region_id', $regionId))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('name', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = Season::query()
            ->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as upcoming', [Season::STATUS_UPCOMING])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as active', [Season::STATUS_ACTIVE])
            ->selectRaw('COALESCE(SUM(licenses_count), 0) as licenses')
            ->first();

        return [
            'seasons' => $seasons,
            'summary' => $summary,
            'regions' => Region::query()->select(['id', 'name'])->orderBy('name')->get(),
            'statuses' => config('government.season_statuses'),
        ];
    }
}
