<?php

namespace App\Services\Captain;

use App\Models\CatchRecord;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * سجل الصيد للكابتن: كل سطر مصيد أعلنه على رحلاته، وملخص بالصنف.
 */
class CatchLog
{
    public function entries(User $captain, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return CatchRecord::query()
            ->whereIn('trip_id', Trip::forCaptain($captain)->select('id'))
            ->with(['species', 'trip.boat'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('trip', fn ($t) => $t->where('trip_number', 'like', "%{$search}%"))
                ->orWhereHas('species', fn ($s) => $s->where('name_ar', 'like', "%{$search}%"))))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * مجموع ما أعلنه الكابتن وما عُدّ منه لكل صنف.
     *
     * @return Collection<int, object{species_id:int, species:string, trips:int, captain_kg:float, counted_kg:float}>
     */
    public function summary(User $captain): Collection
    {
        return CatchRecord::query()
            ->join('species', 'species.id', '=', 'catch_records.species_id')
            ->whereIn('catch_records.trip_id', Trip::forCaptain($captain)->select('id'))
            ->select('species.id AS species_id', 'species.name_ar AS species', 'species.name_sci AS name_sci')
            ->selectRaw('COUNT(DISTINCT catch_records.trip_id) AS trips')
            ->selectRaw('COALESCE(SUM(catch_records.captain_kg), 0) AS captain_kg')
            ->selectRaw('COALESCE(SUM(catch_records.counted_kg), 0) AS counted_kg')
            ->groupBy('species.id', 'species.name_ar', 'species.name_sci')
            ->orderByDesc(DB::raw('SUM(catch_records.captain_kg)'))
            ->get()
            ->map(fn ($row) => (object) [
                'species_id' => (int) $row->species_id,
                'species' => $row->species,
                'name_sci' => $row->name_sci,
                'trips' => (int) $row->trips,
                'captain_kg' => round((float) $row->captain_kg, 2),
                'counted_kg' => round((float) $row->counted_kg, 2),
            ]);
    }
}
