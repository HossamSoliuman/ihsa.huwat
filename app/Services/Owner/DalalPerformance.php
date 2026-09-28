<?php

namespace App\Services\Owner;

use App\Models\Consignment;
use App\Models\DalalInvoiceReview;
use App\Models\DalalPayout;
use App\Models\SaleItem;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * أداء الدلالين عند المالك (من `getDalalPerformanceStats` و`topDalalsChart` في
 * hispa): على فترة مختارة — ما باعه كل دلال من مصيده وبكم، وما اقتطعه، وصافيه
 * له، وسعر الكيلو لصنف بعينه (السعر لا يُقارن عبر أصناف مختلفة)؛ وعلى كل
 * الفترات — نسبة تصريف ما أُرسل إليه، والمستلم منه، والمتبقي عليه.
 */
class DalalPerformance
{
    public const PERIODS = ['month' => 'هذا الشهر', 'quarter' => 'آخر ثلاثة أشهر', 'year' => 'هذا العام', 'all' => 'كل الفترات', 'custom' => 'نطاق مخصص'];

    /** سلاسل مخطط الاتجاه: أعلى خمسة دلالين، والباقون "آخرون". */
    private const TREND_SERIES = 5;

    public function for(User $owner, string $period = 'quarter', ?string $from = null, ?string $to = null, ?int $speciesId = null): array
    {
        [$start, $end, $period] = $this->range($period, $from, $to);

        $inPeriod = fn (Builder $q, string $column) => $q
            ->when($start, fn ($w) => $w->where($column, '>=', $start))
            ->when($end, fn ($w) => $w->where($column, '<=', $end));

        $sold = $inPeriod(SaleItem::soldByDalalFor($owner), 'sales.sold_at')
            ->selectRaw('sales.seller_id AS dalal_id, COUNT(DISTINCT sale_items.sale_id) AS invoices, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS total, SUM(sale_items.commission_amount + sale_items.wage_amount) AS cut, SUM(sale_items.owner_net) AS net')
            ->groupBy('sales.seller_id')->get()->keyBy('dalal_id');
        $sent = $inPeriod(Consignment::forOwner($owner), 'sent_at')
            ->selectRaw('dalal_id, SUM(total_kg) AS kg')->groupBy('dalal_id')->pluck('kg', 'dalal_id');
        $rejected = DalalInvoiceReview::forOwner($owner)->rejected()
            ->whereHas('sale', fn ($q) => $inPeriod($q, 'sold_at'))
            ->selectRaw('dalal_id, COUNT(*) AS n')->groupBy('dalal_id')->pluck('n', 'dalal_id');

        // على كل الفترات: التصريف والرصيد لا معنى لهما داخل نافذة (ما أُرسل قبلها يُباع فيها).
        $sentAll = Consignment::forOwner($owner)->selectRaw('dalal_id, SUM(total_kg) AS kg')->groupBy('dalal_id')->pluck('kg', 'dalal_id');
        $soldAll = SaleItem::soldByDalalFor($owner)->selectRaw('sales.seller_id AS dalal_id, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.owner_net) AS net')
            ->groupBy('sales.seller_id')->get()->keyBy('dalal_id');
        $paidAll = DalalPayout::forOwner($owner)->selectRaw('dalal_id, SUM(amount) AS amount')->groupBy('dalal_id')->pluck('amount', 'dalal_id');

        $ids = $sold->keys()->merge($sent->keys())
            ->merge($soldAll->filter(fn ($row, $id) => round((float) $row->net - (float) ($paidAll[$id] ?? 0), 2) > 0)->keys())
            ->map(fn ($id) => (int) $id)->unique();
        $names = User::whereIn('id', $ids)->pluck('name', 'id');
        $netTotal = (float) $sold->sum('net');

        $rows = $ids->map(function (int $id) use ($sold, $sent, $rejected, $sentAll, $soldAll, $paidAll, $names, $netTotal) {
            $row = $sold[$id] ?? null;
            $kg = round((float) ($row->kg ?? 0), 2);
            $total = round((float) ($row->total ?? 0), 2);
            $cut = round((float) ($row->cut ?? 0), 2);
            $net = round((float) ($row->net ?? 0), 2);
            $netAll = round((float) ($soldAll[$id]->net ?? 0), 2);
            $paid = round((float) ($paidAll[$id] ?? 0), 2);
            $sentKg = (float) ($sentAll[$id] ?? 0);

            return [
                'dalal_id' => $id,
                'dalal' => $names[$id] ?? '—',
                'invoices' => (int) ($row->invoices ?? 0),
                'sold_kg' => $kg,
                'sales_total' => $total,
                'deductions' => $cut,
                'deduction_pct' => $total > 0 ? round($cut / $total * 100, 1) : null,
                'owner_net' => $net,
                'share_pct' => $netTotal > 0 ? round($net / $netTotal * 100, 1) : null,
                'avg_price' => $kg > 0 ? round($total / $kg, 2) : null,
                'sent_kg' => round((float) ($sent[$id] ?? 0), 2),
                'rejected' => (int) ($rejected[$id] ?? 0),
                'sell_through' => $sentKg > 0 ? round((float) ($soldAll[$id]->kg ?? 0) / $sentKg * 100, 1) : null,
                'paid' => $paid,
                'balance' => round($netAll - $paid, 2),
                'paid_ratio' => $netAll > 0 ? round(min($paid / $netAll, 1) * 100, 1) : null,
            ];
        })->sortByDesc('owner_net')->values();

        $kg = (float) $rows->sum('sold_kg');
        $sales = (float) $rows->sum('sales_total');
        $cut = (float) $rows->sum('deductions');
        $prices = $this->prices($owner, $start, $end, $speciesId);

        return [
            'period' => [
                'key' => $period,
                'label' => self::PERIODS[$period],
                'from' => $start?->toDateString(),
                'to' => $end?->toDateString(),
            ],
            'kpis' => [
                'dalals' => $rows->where('invoices', '>', 0)->count(),
                'invoices' => (int) $rows->sum('invoices'),
                'sold_kg' => round($kg, 2),
                'sales_total' => round($sales, 2),
                'deductions' => round($cut, 2),
                'deduction_pct' => $sales > 0 ? round($cut / $sales * 100, 1) : null,
                'owner_net' => round($netTotal, 2),
                'avg_price' => $kg > 0 ? round($sales / $kg, 2) : null,
                'balance' => round($rows->sum('balance'), 2),
                'with_balance' => $rows->where('balance', '>', 0)->count(),
            ],
            'rows' => $rows,
            'highlights' => [
                'top_net' => $rows->where('owner_net', '>', 0)->first(),
                'most_active' => $rows->where('invoices', '>', 0)->sortByDesc('invoices')->first(),
                'best_price' => $prices['rows']->first(),
                'highest_balance' => $rows->where('balance', '>', 0)->sortByDesc('balance')->first(),
            ],
            'prices' => $prices,
            'trend' => $this->trend($owner),
        ];
    }

    /**
     * سعر الكيلو لصنف واحد عند كل دلال في الفترة — الأعلى أوّلًا — ومتوسط
     * بيع المالك المباشر للصنف نفسه للمقارنة. الصنف الافتراضي أكثرها وزنًا.
     *
     * @return array{species: Collection<int, Species>, species_id: int|null, species_name: string|null, rows: Collection<int, array{dalal_id:int, dalal:string, kg:float, avg_price:float}>, direct: float|null}
     */
    private function prices(User $owner, ?Carbon $start, ?Carbon $end, ?int $speciesId): array
    {
        $window = fn (Builder $q) => $q
            ->when($start, fn ($w) => $w->where('sales.sold_at', '>=', $start))
            ->when($end, fn ($w) => $w->where('sales.sold_at', '<=', $end));

        $bySpecies = $window(SaleItem::soldByDalalFor($owner))
            ->selectRaw('sale_items.species_id, SUM(sale_items.weight_kg) AS kg')
            ->groupBy('sale_items.species_id')->orderByDesc('kg')->pluck('kg', 'species_id');

        $species = Species::whereIn('id', $bySpecies->keys())->get(['id', 'name_ar'])
            ->sortByDesc(fn (Species $s) => (float) $bySpecies[$s->id])->values();
        $speciesId = $speciesId && $bySpecies->has($speciesId) ? $speciesId : ($species->first()?->id);

        if ($speciesId === null) {
            return ['species' => $species, 'species_id' => null, 'species_name' => null, 'rows' => collect(), 'direct' => null];
        }

        $rows = $window(SaleItem::soldByDalalFor($owner))->where('sale_items.species_id', $speciesId)
            ->join('users', 'users.id', '=', 'sales.seller_id')
            ->selectRaw('sales.seller_id AS dalal_id, users.name AS dalal, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS total')
            ->groupBy('sales.seller_id', 'users.name')->get()
            ->map(fn ($row) => [
                'dalal_id' => (int) $row->dalal_id,
                'dalal' => $row->dalal,
                'kg' => round((float) $row->kg, 2),
                'avg_price' => (float) $row->kg > 0 ? round((float) $row->total / (float) $row->kg, 2) : 0.0,
            ])
            ->sortByDesc('avg_price')->values();

        $direct = SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.seller_id', $owner->id)
            ->where('sale_items.species_id', $speciesId)
            ->when($start, fn ($w) => $w->where('sales.sold_at', '>=', $start))
            ->when($end, fn ($w) => $w->where('sales.sold_at', '<=', $end))
            ->selectRaw('SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS total')->first();

        return [
            'species' => $species,
            'species_id' => $speciesId,
            'species_name' => $species->firstWhere('id', $speciesId)?->name_ar,
            'rows' => $rows,
            'direct' => (float) ($direct->kg ?? 0) > 0 ? round((float) $direct->total / (float) $direct->kg, 2) : null,
        ];
    }

    /**
     * صافي المالك من كل دلال شهرًا بشهر، آخر ستة أشهر — أعلى خمسة سلاسل
     * مستقلة والباقون مجموعون في "آخرون" (لوحة الألوان ثمانية لا تُدوَّر).
     *
     * @return array{labels: array<int, string>, series: array<int, array{label:string, data:array<int, float>}>}
     */
    private function trend(User $owner): array
    {
        $from = now()->startOfMonth()->subMonths(5);

        $lines = SaleItem::soldByDalalFor($owner)->where('sales.sold_at', '>=', $from)
            ->join('users', 'users.id', '=', 'sales.seller_id')
            ->get(['sales.sold_at', 'sales.seller_id', 'users.name AS dalal', 'sale_items.owner_net']);

        $months = collect(range(0, 5))->map(fn ($i) => $from->copy()->addMonths($i));
        $top = $lines->groupBy('seller_id')->map(fn ($g) => $g->sum('owner_net'))->sortDesc()->keys()->take(self::TREND_SERIES);
        $names = $lines->pluck('dalal', 'seller_id');

        $series = $top->map(fn ($id) => [
            'label' => $names[$id],
            'data' => $months->map(fn (Carbon $m) => round((float) $lines->where('seller_id', $id)->filter(fn ($l) => Carbon::parse($l->sold_at)->format('Y-m') === $m->format('Y-m'))->sum('owner_net'), 2))->all(),
        ])->values();

        $others = $lines->whereNotIn('seller_id', $top->all());
        if ($others->isNotEmpty()) {
            $series->push([
                'label' => 'آخرون',
                'data' => $months->map(fn (Carbon $m) => round((float) $others->filter(fn ($l) => Carbon::parse($l->sold_at)->format('Y-m') === $m->format('Y-m'))->sum('owner_net'), 2))->all(),
            ]);
        }

        return [
            // الشهر والسنة سطران — ستة أشهر تُكتب كلها بلا تخطٍّ.
            'labels' => $months->map(fn (Carbon $m) => [$m->translatedFormat('M'), $m->format('Y')])->all(),
            'series' => $series->all(),
        ];
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null, 2: string}
     */
    private function range(string $period, ?string $from, ?string $to): array
    {
        $period = array_key_exists($period, self::PERIODS) ? $period : 'quarter';

        return match ($period) {
            'month' => [now()->startOfMonth(), now()->endOfDay(), $period],
            'year' => [now()->startOfYear(), now()->endOfDay(), $period],
            'all' => [null, null, $period],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth(),
                $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay(),
                $period,
            ],
            default => [now()->startOfMonth()->subMonths(2), now()->endOfDay(), 'quarter'],
        };
    }
}
