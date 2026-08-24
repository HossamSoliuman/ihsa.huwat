<?php

namespace App\Support;

use App\Models\Boat;
use App\Models\BycatchRecord;
use App\Models\CatchRecord;
use App\Models\Fisher;
use App\Models\MarketAuction;
use App\Models\Port;
use Illuminate\Support\Collection;

/**
 * تجميع النشرة السنوية للمصايد البحرية من سجلات السنة المختارة.
 *
 * النشرة لا تقدّر قيمًا للبيانات الناقصة: كل مؤشر يُحسب من السجلات الموجودة
 * فعلًا، وما لا يوجد له سجل يظهر صفرًا أو شرطة. هذا هو الفرق بين نشرة رسمية
 * وبين لوحة عرض.
 */
class AnnualBulletinService
{
    /** المجموعات التي يُصنَّف إليها الصيد العرضي، بالكلمة الدالة في اسم الكائن. */
    private const BYCATCH_GROUPS = [
        'سلاحف بحرية' => ['سلحفاة', 'سلاحف'],
        'أسماك غضروفية' => ['قرش', 'شفنين', 'أبو منشار'],
        'ثدييات بحرية' => ['دلفين', 'أطوم', 'حوت'],
        'طيور بحرية' => ['طائر', 'نورس'],
    ];

    /** الكائنات التي يستوجب تسجيلها متابعة، لأنها مصنّفة حساسة بيئيًا. */
    private const SENSITIVE_KEYWORDS = ['سلحفاة', 'سلاحف', 'دلفين', 'أطوم', 'حوت', 'قرش'];

    public function build(int $year): array
    {
        $records = CatchRecord::with(['species', 'trip.boat.port', 'trip.departurePort.governorate.region'])
            ->whereYear('recorded_at', $year)
            ->get();

        $trips = $records->pluck('trip')->filter()->unique('id');
        $catchTons = round($records->sum('quantity_kg') / 1000, 2);
        $previousTons = round(
            (float) CatchRecord::whereYear('recorded_at', $year - 1)->sum('quantity_kg') / 1000,
            2
        );

        return [
            'year' => $year,
            'generated_at' => now(),
            'totals' => $this->totals($records, $trips, $catchTons, $previousTons),
            'monthly' => $this->monthly($records),
            'seasons' => $this->seasons($records, $catchTons),
            'by_region' => $this->byRegion($records, $catchTons),
            'by_species' => $this->bySpecies($records, $catchTons),
            'by_port' => $this->byPort($records),
            'top_boats' => $this->topBoats($records),
            'trips' => $this->tripStats($trips, $records),
            'economic' => $this->economic($records, $year),
            'year_comparison' => $this->yearComparison($year),
            'density_points' => $this->densityPoints($records),
            'bycatch' => $this->bycatch($year),
            'statistical_table' => $this->statisticalTable($records),
        ];
    }

    private function totals(Collection $records, Collection $trips, float $catchTons, float $previousTons): array
    {
        $activeBoats = $trips->pluck('boat_id')->filter()->unique()->count();

        return [
            'catch_tons' => $catchTons,
            'approved_kg' => (float) $trips->sum('approved_kg'),
            'trips' => $trips->count(),
            'trips_records' => $records->count(),
            'active_boats' => $activeBoats,
            'registered_boats' => Boat::count(),
            'valid_boat_licenses' => Boat::where('license_status', 'سارية')->count(),
            'ports' => $records->map(fn ($r) => $this->portName($r))->unique()->reject(fn ($n) => $n === 'غير محدد')->count(),
            'registered_ports' => Port::count(),
            'governorates' => $records->map(fn ($r) => $this->governorateName($r))->unique()->reject(fn ($n) => $n === 'غير محدد')->count(),
            'active_fishers' => Fisher::where('status', 'نشط')->count(),
            'species' => $records->pluck('species_id')->unique()->count(),
            'avg_catch_per_trip_kg' => $trips->count() ? round($records->sum('quantity_kg') / $trips->count(), 1) : 0,
            'growth_pct' => $previousTons > 0 ? round(($catchTons - $previousTons) / $previousTons * 100, 1) : null,
        ];
    }

    private function monthly(Collection $records): Collection
    {
        $byMonth = $records->groupBy(fn ($r) => (int) $r->recorded_at->format('n'))
            ->map(fn (Collection $g) => round($g->sum('quantity_kg') / 1000, 2));

        return collect(ProductionReportService::MONTHS)->map(fn (string $label, int $month) => [
            'label' => $label,
            'catch_tons' => (float) ($byMonth[$month] ?? 0),
        ])->values();
    }

    private function seasons(Collection $records, float $catchTons): Collection
    {
        $seasons = [
            'الشتاء' => [12, 1, 2],
            'الربيع' => [3, 4, 5],
            'الصيف' => [6, 7, 8],
            'الخريف' => [9, 10, 11],
        ];

        return collect($seasons)->map(function (array $months, string $season) use ($records, $catchTons) {
            $tons = round($records->filter(fn ($r) => in_array((int) $r->recorded_at->format('n'), $months, true))->sum('quantity_kg') / 1000, 2);

            return [
                'season' => $season,
                'catch_tons' => $tons,
                'share_pct' => $catchTons > 0 ? round($tons / $catchTons * 100, 1) : 0,
            ];
        })->values();
    }

    private function byRegion(Collection $records, float $catchTons): Collection
    {
        return $records->groupBy(fn ($r) => $this->regionName($r))
            ->map(function (Collection $group, string $region) use ($catchTons) {
                $tons = round($group->sum('quantity_kg') / 1000, 2);

                return [
                    'region' => $region,
                    'catch_tons' => $tons,
                    'share_pct' => $catchTons > 0 ? round($tons / $catchTons * 100, 1) : 0,
                    'ports' => $group->map(fn ($r) => $this->portName($r))->unique()->count(),
                ];
            })
            ->sortByDesc('catch_tons')
            ->values();
    }

    private function bySpecies(Collection $records, float $catchTons): Collection
    {
        return $records->groupBy(fn ($r) => $r->species?->name_ar ?? 'غير محدد')
            ->map(function (Collection $group, string $species) use ($catchTons) {
                $tons = round($group->sum('quantity_kg') / 1000, 2);

                return [
                    'species' => $species,
                    'scientific_name' => $group->first()->species?->name_sci,
                    'catch_tons' => $tons,
                    'share_pct' => $catchTons > 0 ? round($tons / $catchTons * 100, 1) : 0,
                    'trips' => $group->pluck('trip_id')->unique()->count(),
                    'boats' => $group->pluck('trip.boat_id')->filter()->unique()->count(),
                ];
            })
            ->sortByDesc('catch_tons')
            ->values();
    }

    private function byPort(Collection $records): Collection
    {
        return $records->groupBy(fn ($r) => $this->portName($r))
            ->map(fn (Collection $group, string $port) => [
                'port' => $port,
                'region' => $this->regionName($group->first()),
                'governorate' => $this->governorateName($group->first()),
                'catch_tons' => round($group->sum('quantity_kg') / 1000, 2),
                'trips' => $group->pluck('trip_id')->unique()->count(),
            ])
            ->sortByDesc('catch_tons')
            ->values();
    }

    private function topBoats(Collection $records): Collection
    {
        return $records->filter(fn ($r) => $r->trip?->boat)
            ->groupBy(fn ($r) => $r->trip->boat->name)
            ->map(fn (Collection $group, string $boat) => [
                'boat' => $boat,
                'port' => $group->first()->trip->boat->port?->name ?? '—',
                'catch_tons' => round($group->sum('quantity_kg') / 1000, 2),
                'trips' => $group->pluck('trip_id')->unique()->count(),
            ])
            ->sortByDesc('catch_tons')
            ->values();
    }

    private function tripStats(Collection $trips, Collection $records): array
    {
        $recordsByMonth = $records->groupBy(fn ($r) => (int) $r->recorded_at->format('n'));

        return [
            'avg_duration_hours' => $trips->whereNotNull('duration_hours')->avg('duration_hours') ?? 0,
            'by_month' => collect(ProductionReportService::MONTHS)->map(fn (string $label, int $month) => [
                'label' => $label,
                'trips' => isset($recordsByMonth[$month]) ? $recordsByMonth[$month]->pluck('trip_id')->unique()->count() : 0,
            ])->values(),
            'by_gear' => $trips->groupBy(fn ($t) => $t->gear_type ?? 'غير محدد')
                ->map(fn (Collection $g, string $gear) => ['gear' => $gear, 'count' => $g->count()])
                ->sortByDesc('count')->values(),
            'by_status' => $trips->groupBy('status')
                ->map(fn (Collection $g, string $status) => ['status' => $status, 'count' => $g->count()])
                ->sortByDesc('count')->values(),
        ];
    }

    private function economic(Collection $records, int $year): array
    {
        $valued = $records->filter(fn ($r) => $r->price_per_kg > 0);
        $value = fn (CatchRecord $r) => (float) $r->quantity_kg * (float) $r->price_per_kg;
        $totalValue = $valued->sum($value);
        $valuedKg = (float) $valued->sum('quantity_kg');

        $topPort = $valued->groupBy(fn ($r) => $this->portName($r))->map(fn (Collection $g) => $g->sum($value))->sortDesc();
        $topSpecies = $valued->groupBy(fn ($r) => $r->species?->name_ar ?? 'غير محدد')->map(fn (Collection $g) => $g->sum($value))->sortDesc();
        $auctions = MarketAuction::whereYear('auction_date', $year)->get();

        return [
            'estimated_value_sar' => round($totalValue, 2),
            'avg_price_sar_kg' => $valuedKg > 0 ? round($totalValue / $valuedKg, 2) : 0,
            'top_port' => ['name' => $topPort->keys()->first(), 'value_sar' => round((float) $topPort->first(), 2)],
            'top_species' => ['name' => $topSpecies->keys()->first(), 'value_sar' => round((float) $topSpecies->first(), 2)],
            'auctions' => $auctions->count(),
            'sold_tons' => round($auctions->sum('quantity_sold_kg') / 1000, 2),
        ];
    }

    private function yearComparison(int $year): Collection
    {
        return collect(range($year - 4, $year))->map(fn (int $y) => [
            'year' => $y,
            'catch_tons' => round((float) CatchRecord::whereYear('recorded_at', $y)->sum('quantity_kg') / 1000, 2),
        ])->values();
    }

    /**
     * نقاط الكثافة على الخريطة — الموانئ التي لها إحداثيات وإنتاج مسجّل في السنة.
     */
    private function densityPoints(Collection $records): Collection
    {
        return $records->filter(fn ($r) => $r->trip?->departurePort?->lat !== null && $r->trip?->departurePort?->lng !== null)
            ->groupBy(fn ($r) => $r->trip->departurePort->name)
            ->map(fn (Collection $group, string $port) => [
                'port' => $port,
                'lat' => (float) $group->first()->trip->departurePort->lat,
                'lng' => (float) $group->first()->trip->departurePort->lng,
                'catch_tons' => round($group->sum('quantity_kg') / 1000, 2),
            ])
            ->sortByDesc('catch_tons')
            ->values();
    }

    private function bycatch(int $year): array
    {
        $records = BycatchRecord::whereYear('created_at', $year)->get();
        $isSensitive = fn (string $name) => collect(self::SENSITIVE_KEYWORDS)->contains(fn ($k) => str_contains($name, $k));
        $released = $records->filter(fn ($r) => str_contains((string) $r->action_taken, 'إعادة'));

        $groups = $records->groupBy(fn ($r) => $this->bycatchGroup($r->species_name))
            ->map(fn (Collection $group, string $name) => [
                'group' => $name,
                'cases' => $group->count(),
                'count' => $group->count(),
                'weight_kg' => round((float) $group->sum('quantity_kg'), 1),
            ])
            ->sortByDesc('weight_kg')
            ->values();

        return [
            'cases' => $records->count(),
            'organisms' => $records->count(),
            'weight_kg' => round((float) $records->sum('quantity_kg'), 1),
            'release_rate_pct' => $records->count() ? round($released->count() / $records->count() * 100, 1) : 0,
            'sensitive_cases' => $records->filter(fn ($r) => $isSensitive((string) $r->species_name))->count(),
            'groups' => $groups,
        ];
    }

    private function bycatchGroup(string $speciesName): string
    {
        foreach (self::BYCATCH_GROUPS as $group => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($speciesName, $keyword)) {
                    return $group;
                }
            }
        }

        return 'كائنات أخرى';
    }

    private function statisticalTable(Collection $records): Collection
    {
        return $records->groupBy(fn ($r) => $this->regionName($r).'|'.$this->governorateName($r).'|'.$this->portName($r))
            ->map(function (Collection $group, string $key) {
                [$region, $governorate, $port] = explode('|', $key);

                return [
                    'region' => $region,
                    'governorate' => $governorate,
                    'port' => $port,
                    'catch_tons' => round($group->sum('quantity_kg') / 1000, 2),
                    'trips' => $group->pluck('trip_id')->unique()->count(),
                    'boats' => $group->pluck('trip.boat_id')->filter()->unique()->count(),
                    'species_count' => $group->pluck('species_id')->unique()->count(),
                ];
            })
            ->sortByDesc('catch_tons')
            ->values();
    }

    private function portName(CatchRecord $record): string
    {
        return $record->trip?->departurePort?->name ?? 'غير محدد';
    }

    private function governorateName(CatchRecord $record): string
    {
        return $record->trip?->departurePort?->governorate?->name ?? 'غير محدد';
    }

    private function regionName(CatchRecord $record): string
    {
        return $record->trip?->departurePort?->governorate?->region?->name ?? 'غير محدد';
    }
}
