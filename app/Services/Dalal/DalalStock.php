<?php

namespace App\Services\Dalal;

use App\Models\Consignment;
use App\Models\Species;
use App\Models\StockMovement;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * مخزون الدلال مقروءًا من دفتر المخزون: كل ما أرسله المالك يدخل الدفتر على
 * الدلال بالرحلة والصنف، فـ"دفعة" المخزون = (رحلة × صنف) ومتاحها مجموع
 * حركاتها. الدفعة تحمل مالكها من رحلتها — منه تُحسب العمولة وصافي المالك
 * عند البيع، وبها يُجمّع المخزون حسب المالك كما في شاشة التطبيق.
 */
class DalalStock
{
    /**
     * دفعات المخزون المتاحة، الأقدم استلامًا أوّلًا (ترتيب الصرف عند البيع).
     *
     * @return Collection<int, array{trip_id:int|null, trip_number:string|null, owner_id:int|null, owner:string|null, boat:string|null, species_id:int, species:string|null, name_sci:string|null, available_kg:float, received_at:string|null}>
     */
    public function lots(User $dalal, ?int $speciesId = null): Collection
    {
        $rows = StockMovement::forHolder($dalal)
            ->when($speciesId, fn ($q) => $q->where('species_id', $speciesId))
            ->selectRaw('trip_id, species_id, SUM(weight_kg) AS kg, MIN(created_at) AS first_in, MIN(id) AS first_id')
            ->groupBy('trip_id', 'species_id')
            ->havingRaw('SUM(weight_kg) > 0')
            ->orderBy('first_id')
            ->get();

        $trips = Trip::with(['owner:id,name', 'boat:id,name'])->whereIn('id', $rows->pluck('trip_id')->filter())->get(['id', 'trip_number', 'owner_id', 'boat_id'])->keyBy('id');
        $species = Species::whereIn('id', $rows->pluck('species_id'))->get(['id', 'name_ar', 'name_sci'])->keyBy('id');

        return $rows->map(function ($row) use ($trips, $species) {
            $trip = $trips[$row->trip_id] ?? null;

            return [
                'trip_id' => $row->trip_id !== null ? (int) $row->trip_id : null,
                'trip_number' => $trip?->trip_number,
                'owner_id' => $trip?->owner_id,
                'owner' => $trip?->owner?->name,
                'boat' => $trip?->boat?->name,
                'species_id' => (int) $row->species_id,
                'species' => $species[$row->species_id]->name_ar ?? null,
                'name_sci' => $species[$row->species_id]->name_sci ?? null,
                'available_kg' => round((float) $row->kg, 2),
                'received_at' => $row->first_in ? (string) $row->first_in : null,
            ];
        })->values();
    }

    /**
     * المخزون مجمّعًا حسب المالك (محمود: صنفان، 55 كجم) ثم دفعاته.
     *
     * @return Collection<int, array{owner_id:int|null, owner:string|null, species_count:int, total_kg:float, lots:Collection}>
     */
    public function byOwner(User $dalal): Collection
    {
        return $this->lots($dalal)
            ->groupBy(fn ($lot) => $lot['owner_id'] ?? 0)
            ->map(fn (Collection $lots) => [
                'owner_id' => $lots->first()['owner_id'],
                'owner' => $lots->first()['owner'] ?? '—',
                'species_count' => $lots->pluck('species_id')->unique()->count(),
                'total_kg' => round($lots->sum('available_kg'), 2),
                'lots' => $lots->values(),
            ])
            ->sortByDesc('total_kg')
            ->values();
    }

    /**
     * المتاح لكل صنف عبر الدفعات كلها — قائمة الأصناف في شاشة البيع.
     *
     * @return Collection<int, array{species_id:int, species:string|null, name_sci:string|null, available_kg:float}>
     */
    public function bySpecies(User $dalal): Collection
    {
        return $this->lots($dalal)
            ->groupBy('species_id')
            ->map(fn (Collection $lots) => [
                'species_id' => $lots->first()['species_id'],
                'species' => $lots->first()['species'],
                'name_sci' => $lots->first()['name_sci'],
                'available_kg' => round($lots->sum('available_kg'), 2),
            ])
            ->sortByDesc('available_kg')
            ->values();
    }

    /**
     * بطاقات رأس شاشة المخزون.
     */
    public function summary(User $dalal): array
    {
        $lots = $this->lots($dalal);

        return [
            'species_count' => $lots->pluck('species_id')->unique()->count(),
            'total_kg' => round($lots->sum('available_kg'), 2),
            'owners_count' => $lots->pluck('owner_id')->filter()->unique()->count(),
            'trips_count' => $lots->pluck('trip_id')->filter()->unique()->count(),
            'consignments_count' => Consignment::forDalal($dalal)->count(),
        ];
    }
}
