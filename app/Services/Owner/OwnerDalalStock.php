<?php

namespace App\Services\Owner;

use App\Models\Consignment;
use App\Models\DalalInvoiceReview;
use App\Models\SaleItem;
use App\Models\Species;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\Trip;
use App\Models\User;
use App\Services\Stock\StockLedger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * مخزون الدلالين من مصيد المالك، مقروءًا من دفتر المخزون: كل ما أرسله من
 * رحلة إلى دلال دخل دفتر الدلال بالرحلة والصنف، وكل ما باعه خرج منه. فلكل
 * (دلال × رحلة × صنف): المُرسَل، والمباع، وحركات أخرى (مرتجع / تسوية)،
 * والباقي عنده الآن = مجموع الحركات — ومن سطور فواتيره قيمة المباع وصافيه.
 * يُجمَّع حسب القارب ثم الرحلة كما في hispa (مخزون الدلال حسب القارب ← الرحلة ← الدلال).
 */
class OwnerDalalStock
{
    /**
     * @param  array{boat_id?:int|string|null, dalal_id?:int|string|null, trip_id?:int|string|null, holding?:bool}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(User $owner, array $filters = []): Collection
    {
        $trips = Trip::forOwner($owner)
            ->when($filters['boat_id'] ?? null, fn ($q, $id) => $q->where('boat_id', $id))
            ->when($filters['trip_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->select('id');

        $consignIn = StockMovementType::named(StockLedger::CONSIGN_IN)->id;
        $sale = StockMovementType::named(StockLedger::SALE)->id;

        $movements = StockMovement::whereIn('trip_id', $trips)
            ->where('holder_id', '!=', $owner->id)
            ->when($filters['dalal_id'] ?? null, fn ($q, $id) => $q->where('holder_id', $id))
            ->selectRaw(
                'holder_id, trip_id, species_id,
                SUM(CASE WHEN stock_movement_type_id = ? THEN weight_kg ELSE 0 END) AS consigned,
                SUM(CASE WHEN stock_movement_type_id = ? THEN -weight_kg ELSE 0 END) AS sold,
                SUM(weight_kg) AS remaining,
                MIN(created_at) AS first_in',
                [$consignIn, $sale],
            )
            ->groupBy('holder_id', 'trip_id', 'species_id')
            ->get();

        if ($movements->isEmpty()) {
            return collect();
        }

        $values = SaleItem::soldByDalalFor($owner)
            ->whereIn('sale_items.trip_id', $movements->pluck('trip_id')->unique())
            ->selectRaw('sales.seller_id, sale_items.trip_id, sale_items.species_id, SUM(sale_items.total) AS total, SUM(sale_items.owner_net) AS net')
            ->groupBy('sales.seller_id', 'sale_items.trip_id', 'sale_items.species_id')
            ->get()
            ->keyBy(fn ($row) => "{$row->seller_id}:{$row->trip_id}:{$row->species_id}");

        $tripRows = Trip::with('boat:id,name')->whereIn('id', $movements->pluck('trip_id')->unique())->get(['id', 'trip_number', 'boat_id', 'departure_time'])->keyBy('id');
        $dalals = User::whereIn('id', $movements->pluck('holder_id')->unique())->pluck('name', 'id');
        $species = Species::whereIn('id', $movements->pluck('species_id')->unique())->get(['id', 'name_ar'])->keyBy('id');

        return $movements->map(function ($row) use ($values, $tripRows, $dalals, $species) {
            $trip = $tripRows[$row->trip_id] ?? null;
            $value = $values["{$row->holder_id}:{$row->trip_id}:{$row->species_id}"] ?? null;
            $consigned = round((float) $row->consigned, 2);
            $sold = round((float) $row->sold, 2);
            $remaining = round((float) $row->remaining, 2);
            $firstIn = $row->first_in ? Carbon::parse($row->first_in) : null;

            return [
                'dalal_id' => (int) $row->holder_id,
                'dalal' => $dalals[$row->holder_id] ?? '—',
                'trip_id' => (int) $row->trip_id,
                'trip_number' => $trip?->trip_number,
                'boat_id' => $trip?->boat_id,
                'boat' => $trip?->boat?->name ?? '—',
                'species_id' => (int) $row->species_id,
                'species' => $species[$row->species_id]->name_ar ?? '—',
                'consigned_kg' => $consigned,
                'sold_kg' => $sold,
                'other_kg' => round($remaining - $consigned + $sold, 2),
                'remaining_kg' => $remaining,
                'sales_total' => round((float) ($value->total ?? 0), 2),
                'owner_net' => round((float) ($value->net ?? 0), 2),
                'avg_price' => $sold > 0 ? round((float) ($value->total ?? 0) / $sold, 2) : null,
                'first_in' => $firstIn,
                'age_days' => $remaining > 0 && $firstIn ? (int) $firstIn->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null,
            ];
        })
            ->when($filters['holding'] ?? false, fn ($rows) => $rows->where('remaining_kg', '>', 0))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{consigned_kg: float, sold_kg: float, other_kg: float, remaining_kg: float, sales_total: float, owner_net: float, sell_through: float|null, dalals: int, holding_dalals: int, boats: int, trips: int}
     */
    public function totals(Collection $rows): array
    {
        return $this->sum($rows) + [
            'dalals' => $rows->pluck('dalal_id')->unique()->count(),
            'holding_dalals' => $rows->where('remaining_kg', '>', 0)->pluck('dalal_id')->unique()->count(),
            'boats' => $rows->pluck('boat_id')->filter()->unique()->count(),
            'trips' => $rows->pluck('trip_id')->unique()->count(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function byBoat(Collection $rows): Collection
    {
        return $rows->groupBy('boat_id')->map(fn (Collection $group) => [
            'boat_id' => $group->first()['boat_id'],
            'boat' => $group->first()['boat'],
            'trips' => $group->pluck('trip_id')->unique()->count(),
            'dalals' => $group->pluck('dalal_id')->unique()->count(),
        ] + $this->sum($group))
            ->sortByDesc('remaining_kg')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function byTrip(Collection $rows): Collection
    {
        return $rows->groupBy('trip_id')->map(fn (Collection $group) => [
            'trip_id' => $group->first()['trip_id'],
            'trip_number' => $group->first()['trip_number'],
            'boat' => $group->first()['boat'],
            'dalals' => $group->pluck('dalal')->unique()->values()->all(),
            'oldest_days' => $group->max('age_days'),
        ] + $this->sum($group))
            ->sortByDesc('trip_id')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function byDalal(Collection $rows): Collection
    {
        return $rows->groupBy('dalal_id')->map(fn (Collection $group) => [
            'dalal_id' => $group->first()['dalal_id'],
            'dalal' => $group->first()['dalal'],
            'species' => $group->sortByDesc('consigned_kg')->values(),
        ] + $this->sum($group))
            ->sortByDesc('consigned_kg')->values();
    }

    /**
     * تفاصيل رحلة: إرسالاتها للدلالين وسطور بيعهم منها بفواتيرهم.
     *
     * @return array{consignments: Collection<int, Consignment>, sales: Collection<int, SaleItem>, reviews: Collection<int|string, DalalInvoiceReview>}
     */
    public function tripActivity(User $owner, Trip $trip): array
    {
        $sales = SaleItem::soldByDalalFor($owner)
            ->select('sale_items.*')
            ->where('sale_items.trip_id', $trip->id)
            ->with(['sale:id,invoice_number,sold_at,seller_id', 'sale.seller:id,name', 'species:id,name_ar'])
            ->orderByDesc('sales.sold_at')
            ->get();

        return [
            'consignments' => Consignment::forOwner($owner)->where('trip_id', $trip->id)->with(['dalal:id,name', 'items.species:id,name_ar'])->latest('sent_at')->get(),
            'sales' => $sales,
            'reviews' => DalalInvoiceReview::forOwner($owner)->whereIn('sale_id', $sales->pluck('sale_id')->unique())->get()->keyBy('sale_id'),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{consigned_kg: float, sold_kg: float, other_kg: float, remaining_kg: float, sales_total: float, owner_net: float, sell_through: float|null}
     */
    private function sum(Collection $rows): array
    {
        $consigned = round($rows->sum('consigned_kg'), 2);
        $sold = round($rows->sum('sold_kg'), 2);

        return [
            'consigned_kg' => $consigned,
            'sold_kg' => $sold,
            'other_kg' => round($rows->sum('other_kg'), 2),
            'remaining_kg' => round($rows->sum('remaining_kg'), 2),
            'sales_total' => round($rows->sum('sales_total'), 2),
            'owner_net' => round($rows->sum('owner_net'), 2),
            'sell_through' => $consigned > 0 ? round($sold / $consigned * 100, 1) : null,
        ];
    }
}
