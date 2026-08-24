<?php

namespace App\Support;

use App\Models\CatchRecord;
use Illuminate\Support\Collection;

/**
 * تجميع تقرير الإنتاج لفترة واحدة — يومية أو شهرية أو سنوية.
 *
 * المصدر الوحيد للكميات هو سجلات المصيد (catch_records)، لأنها الوحيدة التي
 * تحمل تاريخًا صالحًا للتقطيع الزمني. الرحلة والقارب والميناء والمنطقة تُشتق
 * من علاقة السجل بالرحلة، فلا يظهر ميناء بلا إنتاج مسجّل في التقرير.
 */
class ProductionReportService
{
    public const PERIODS = ['daily' => 'يومي', 'monthly' => 'شهري', 'yearly' => 'سنوي'];

    public const MONTHS = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    /**
     * @return array{period_label: string, totals: array<string, mixed>, by_region: Collection, by_port: Collection, by_species: Collection}
     */
    public function build(string $period, int $year, int $month, int $day, ?string $region = null): array
    {
        $records = $this->records($period, $year, $month, $day, $region);
        $trips = $records->pluck('trip')->filter()->unique('id');

        return [
            'period_label' => $this->periodLabel($period, $year, $month, $day),
            'totals' => [
                'records' => $records->count(),
                'catch_kg' => (float) $records->sum('quantity_kg'),
                'approved_kg' => (float) $trips->sum('approved_kg'),
                'trips' => $trips->count(),
                'boats' => $trips->pluck('boat_id')->unique()->count(),
                'species' => $records->pluck('species_id')->unique()->count(),
                'value_sar' => (float) $records->sum(fn ($r) => (float) $r->quantity_kg * (float) ($r->price_per_kg ?? 0)),
            ],
            'by_region' => $this->byRegion($records),
            'by_port' => $this->byPort($records),
            'by_species' => $this->bySpecies($records),
        ];
    }

    /**
     * سجلات المصيد داخل الفترة، محمّلة بسلسلة الرحلة → الميناء → المحافظة → المنطقة.
     */
    private function records(string $period, int $year, int $month, int $day, ?string $region): Collection
    {
        $query = CatchRecord::with(['species', 'trip.boat', 'trip.departurePort.governorate.region'])
            ->whereYear('recorded_at', $year);

        if ($period !== 'yearly') {
            $query->whereMonth('recorded_at', $month);
        }

        if ($period === 'daily') {
            $query->whereDay('recorded_at', $day);
        }

        $records = $query->get();

        return $region
            ? $records->filter(fn ($r) => $this->regionOf($r) === $region)->values()
            : $records;
    }

    private function byRegion(Collection $records): Collection
    {
        return $records->groupBy(fn ($r) => $this->regionOf($r))
            ->map(fn (Collection $group, string $name) => [
                'region' => $name,
                'catch_kg' => (float) $group->sum('quantity_kg'),
                'trips' => $group->pluck('trip_id')->unique()->count(),
                'ports' => $group->map(fn ($r) => $this->portOf($r))->unique()->count(),
                'boats' => $group->pluck('trip.boat_id')->filter()->unique()->count(),
            ])
            ->sortByDesc('catch_kg')
            ->values();
    }

    private function byPort(Collection $records): Collection
    {
        return $records->groupBy(fn ($r) => $this->portOf($r))
            ->map(fn (Collection $group, string $name) => [
                'port' => $name,
                'region' => $this->regionOf($group->first()),
                'governorate' => $group->first()->trip?->departurePort?->governorate?->name ?? 'غير محدد',
                'catch_kg' => (float) $group->sum('quantity_kg'),
                'trips' => $group->pluck('trip_id')->unique()->count(),
                'boats' => $group->pluck('trip.boat_id')->filter()->unique()->count(),
                'species_count' => $group->pluck('species_id')->unique()->count(),
            ])
            ->sortByDesc('catch_kg')
            ->values();
    }

    private function bySpecies(Collection $records): Collection
    {
        return $records->groupBy(fn ($r) => $r->species?->name_ar ?? 'غير محدد')
            ->map(fn (Collection $group, string $name) => [
                'species' => $name,
                'catch_kg' => (float) $group->sum('quantity_kg'),
                'records' => $group->count(),
                'trips' => $group->pluck('trip_id')->unique()->count(),
            ])
            ->sortByDesc('catch_kg')
            ->values();
    }

    private function regionOf(CatchRecord $record): string
    {
        return $record->trip?->departurePort?->governorate?->region?->name ?? 'غير محدد';
    }

    private function portOf(CatchRecord $record): string
    {
        return $record->trip?->departurePort?->name ?? 'غير محدد';
    }

    private function periodLabel(string $period, int $year, int $month, int $day): string
    {
        return match ($period) {
            'daily' => sprintf('اليومي %d-%02d-%02d', $year, $month, $day),
            'yearly' => 'السنوي '.$year,
            default => 'الشهري '.(self::MONTHS[$month] ?? $month).' '.$year,
        };
    }
}
