<?php

namespace App\Services\Owner;

use App\Models\Asset;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * إهلاك الأصول بالقسط الثابت شهريًا (قاعدة hispa):
 * القسط = (التكلفة − قيمة الخردة) ÷ العمر بالسنوات ÷ 12، يُحمَّل من شهر
 * الشراء (شاملًا) لمدة العمر × 12 شهرًا. القسط الأخير يأخذ فرق التقريب حتى
 * يساوي المجموع القيمةَ القابلة للإهلاك بالضبط.
 *
 * خلافًا لـhispa (الذي يُسقط الأصل غير النشط من كل الأشهر): الأصل المباع أو
 * التالف يُهلَك حتى شهر التخلّص شاملًا ثم يتوقف — فلا تتغير أشهر مضت.
 *
 * يُحسب ولا يُخزَّن؛ إغلاق الشهر (O4) يثبّت `forMonth`.
 */
class AssetDepreciation
{
    public function depreciable(Asset $asset): float
    {
        return round(max(0, $asset->purchase_cost - $asset->salvage_value), 2);
    }

    public function totalMonths(Asset $asset): int
    {
        return max(0, $asset->useful_life_years * 12);
    }

    public function monthly(Asset $asset): float
    {
        $months = $this->totalMonths($asset);

        return $months > 0 ? round($this->depreciable($asset) / $months, 2) : 0.0;
    }

    /**
     * قسط الأصل في الشهر المعطى (صفر خارج عمره أو بعد التخلّص منه).
     */
    public function chargeFor(Asset $asset, int $year, int $month): float
    {
        $index = $this->monthIndex($asset, CarbonImmutable::create($year, $month, 1));

        if ($index === null) {
            return 0.0;
        }

        $total = $this->totalMonths($asset);
        $monthly = $this->monthly($asset);

        // القسط الأخير يستوعب فرق التقريب.
        return $index === $total - 1
            ? round($this->depreciable($asset) - $monthly * ($total - 1), 2)
            : $monthly;
    }

    /**
     * موقف الأصل في نهاية شهر معيّن (افتراضيًا الشهر الحالي).
     *
     * @return array{monthly: float, total_months: int, months_charged: int, remaining_months: int, accumulated: float, book_value: float, remaining: float}
     */
    public function position(Asset $asset, ?CarbonImmutable $asOf = null): array
    {
        $asOf = ($asOf ?? CarbonImmutable::now())->startOfMonth();
        $total = $this->totalMonths($asset);
        $start = CarbonImmutable::parse($asset->purchase_date)->startOfMonth();

        $charged = $asOf->lessThan($start) ? 0 : (int) $start->diffInMonths($asOf) + 1;

        if ($asset->disposed_at !== null) {
            $disposed = CarbonImmutable::parse($asset->disposed_at)->startOfMonth();
            $charged = min($charged, $disposed->lessThan($start) ? 0 : (int) $start->diffInMonths($disposed) + 1);
        }

        $charged = max(0, min($charged, $total));
        $accumulated = $charged >= $total
            ? $this->depreciable($asset)
            : round($this->monthly($asset) * $charged, 2);

        return [
            'monthly' => $this->monthly($asset),
            'total_months' => $total,
            'months_charged' => $charged,
            'remaining_months' => $asset->disposed_at ? 0 : $total - $charged,
            'accumulated' => $accumulated,
            'book_value' => round($asset->purchase_cost - $accumulated, 2),
            'remaining' => $asset->disposed_at ? 0.0 : round($this->depreciable($asset) - $accumulated, 2),
        ];
    }

    /**
     * إهلاك شهر لأصول المالك (أو قارب واحد) — مدخل إغلاق الشهر.
     *
     * @return array{total: float, assets: array<int, array{id: int, name: string, boat_id: ?int, amount: float}>}
     */
    public function forMonth(User $owner, int $year, int $month, ?int $boatId = null): array
    {
        $rows = [];

        foreach ($this->assets($owner, $boatId) as $asset) {
            $amount = $this->chargeFor($asset, $year, $month);

            if ($amount > 0) {
                $rows[] = ['id' => $asset->id, 'name' => $asset->name, 'boat_id' => $asset->boat_id, 'amount' => $amount];
            }
        }

        return ['total' => round(array_sum(array_column($rows, 'amount')), 2), 'assets' => $rows];
    }

    /**
     * جدول إهلاك سنة: مجموع كل شهر وتراكمه، وتفصيل كل أصل.
     *
     * @return array{months: array<int, array{total: float, accumulated: float}>, year_total: float, assets: array<int, array<string, mixed>>}
     */
    public function forYear(User $owner, int $year, ?int $boatId = null): array
    {
        $months = array_fill(1, 12, 0.0);
        $assets = [];

        foreach ($this->assets($owner, $boatId) as $asset) {
            $perMonth = [];

            for ($m = 1; $m <= 12; $m++) {
                $perMonth[$m] = $this->chargeFor($asset, $year, $m);
                $months[$m] += $perMonth[$m];
            }

            $yearTotal = round(array_sum($perMonth), 2);

            if ($yearTotal <= 0) {
                continue;
            }

            $assets[] = [
                'asset' => $asset,
                'months' => $perMonth,
                'months_charged' => count(array_filter($perMonth)),
                'year_total' => $yearTotal,
            ] + $this->position($asset, CarbonImmutable::create($year, 12, 1));
        }

        $running = 0.0;
        $schedule = [];

        foreach ($months as $m => $total) {
            $running += $total;
            $schedule[$m] = ['total' => round($total, 2), 'accumulated' => round($running, 2)];
        }

        return ['months' => $schedule, 'year_total' => round($running, 2), 'assets' => $assets];
    }

    /**
     * سجل الأصول: كل أصل بموقفه اليوم، مع الربح/الخسارة عند التخلّص.
     *
     * @return array{rows: Collection, totals: array{count: int, cost: float, accumulated: float, book_value: float}}
     */
    public function register(Collection $assets): array
    {
        $rows = $assets->map(function (Asset $asset) {
            $position = $this->position($asset);
            $gain = $asset->disposed_at !== null && $asset->disposal_value !== null
                ? round($asset->disposal_value - $position['book_value'], 2)
                : null;

            return ['asset' => $asset, 'disposal_gain' => $gain] + $position;
        });

        return [
            'rows' => $rows,
            'totals' => [
                'count' => $rows->count(),
                'cost' => round($rows->sum(fn ($r) => $r['asset']->purchase_cost), 2),
                'accumulated' => round($rows->sum('accumulated'), 2),
                'book_value' => round($rows->sum('book_value'), 2),
            ],
        ];
    }

    /**
     * ترتيب الشهر داخل عمر الأصل (0 = شهر الشراء)، أو null خارجه.
     */
    private function monthIndex(Asset $asset, CarbonImmutable $monthStart): ?int
    {
        $total = $this->totalMonths($asset);
        $start = CarbonImmutable::parse($asset->purchase_date)->startOfMonth();

        if ($total <= 0 || $monthStart->lessThan($start)) {
            return null;
        }

        if ($asset->disposed_at !== null && $monthStart->greaterThan(CarbonImmutable::parse($asset->disposed_at)->startOfMonth())) {
            return null;
        }

        $index = (int) $start->diffInMonths($monthStart);

        return $index < $total ? $index : null;
    }

    private function assets(User $owner, ?int $boatId): Collection
    {
        return Asset::forOwner($owner)
            ->when($boatId !== null, fn ($q) => $q->where('boat_id', $boatId))
            ->with(['type', 'boat'])
            ->orderBy('purchase_date')
            ->get();
    }
}
