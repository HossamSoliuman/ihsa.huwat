<?php

namespace App\Services\Dalal;

use App\Models\Customer;
use App\Models\DalalPartnership;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\User;
use App\Services\Stock\StockLedger;
use Illuminate\Support\Carbon;

/**
 * أرقام رئيسة الدلال — تُقرأ في الويب وفي التطبيق من المكان نفسه. المؤشرات
 * على فترة مختارة (اليوم / الأسبوع / الشهر / السنة / نطاق مخصص) كما في
 * لوحته القديمة؛ المخطط آخر ستة أشهر دائمًا.
 */
class DalalDashboard
{
    public const PERIODS = ['today' => 'اليوم', 'week' => 'هذا الأسبوع', 'month' => 'هذا الشهر', 'year' => 'هذا العام', 'custom' => 'نطاق مخصص'];

    public function __construct(
        private readonly DalalStock $stock,
        private readonly DalalAccounts $accounts,
    ) {}

    public function for(User $dalal, string $period = 'month', ?string $from = null, ?string $to = null): array
    {
        [$start, $end, $period] = $this->range($period, $from, $to);

        $sales = Sale::forSeller($dalal)->whereBetween('sold_at', [$start, $end]);
        $items = SaleItem::whereHas('sale', fn ($q) => $q->where('seller_id', $dalal->id)->whereBetween('sold_at', [$start, $end]));

        $revenue = (float) (clone $sales)->sum('total');
        $kgSold = (float) (clone $items)->sum('weight_kg');
        $profit = (float) (clone $sales)->sum('commission_amount') + (float) (clone $sales)->sum('wage_amount');

        return [
            'period' => ['key' => $period, 'label' => self::PERIODS[$period], 'from' => $start->toDateString(), 'to' => $end->toDateString()],
            'kpis' => [
                'received_kg' => round((float) StockMovement::forHolder($dalal)
                    ->where('stock_movement_type_id', StockMovementType::named(StockLedger::CONSIGN_IN)->id)
                    ->whereBetween('created_at', [$start, $end])->sum('weight_kg'), 2),
                'sales_total' => round($revenue, 2),
                'sales_count' => (clone $sales)->count(),
                'net_profit' => round($profit, 2),
                'commission' => round((float) (clone $sales)->sum('commission_amount'), 2),
                'wages' => round((float) (clone $sales)->sum('wage_amount'), 2),
                'sold_kg' => round($kgSold, 2),
                'avg_price_per_kg' => $kgSold > 0 ? round((float) (clone $items)->sum('total') / $kgSold, 2) : null,
                'active_customers' => (clone $sales)->whereNotNull('customer_id')->distinct()->count('customer_id'),
                'unpaid' => round((float) Sale::forSeller($dalal)->selectRaw('COALESCE(SUM(total - paid_amount), 0) AS due')->value('due'), 2),
                'in_stock_kg' => $this->stock->summary($dalal)['total_kg'],
                'due_to_owners' => round($this->accounts->owners($dalal)->sum('due'), 2),
                'customers' => Customer::forAccount($dalal)->count(),
            ],
            'revenue_by_month' => $this->revenueByMonth($dalal),
            'top_species' => (clone $items)->join('species', 'species.id', '=', 'sale_items.species_id')
                ->selectRaw('species.name_ar AS species, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS total')
                ->groupBy('species.id', 'species.name_ar')
                ->orderByDesc('kg')->limit(6)->get()
                ->map(fn ($row) => ['species' => $row->species, 'weight_kg' => round((float) $row->kg, 2), 'total' => round((float) $row->total, 2)])
                ->values(),
            'pending_requests' => DalalPartnership::forDalal($dalal)->pending()->with('owner:id,name,phone')->latest()->limit(5)->get(),
            'recent_sales' => Sale::forSeller($dalal)->with(['customer', 'items.species'])->latest('sold_at')->limit(10)->get(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function range(string $period, ?string $from, ?string $to): array
    {
        $period = array_key_exists($period, self::PERIODS) ? $period : 'month';

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay(), $period],
            'week' => [now()->startOfWeek(Carbon::SATURDAY), now()->endOfDay(), $period],
            'year' => [now()->startOfYear(), now()->endOfDay(), $period],
            'custom' => [
                $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth(),
                $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay(),
                $period,
            ],
            default => [now()->startOfMonth(), now()->endOfDay(), 'month'],
        };
    }

    /**
     * الإيرادات وربح الدلال (العمولة + الأجور) آخر ستة أشهر.
     */
    private function revenueByMonth(User $dalal): array
    {
        $from = now()->startOfMonth()->subMonths(5);

        $rows = Sale::forSeller($dalal)->where('sold_at', '>=', $from)
            ->get(['sold_at', 'total', 'commission_amount', 'wage_amount'])
            ->groupBy(fn (Sale $sale) => $sale->sold_at->format('Y-m'));

        $series = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $from->copy()->addMonths($i);
            $group = $rows[$month->format('Y-m')] ?? collect();
            $series[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->translatedFormat('M Y'),
                'total' => round((float) $group->sum('total'), 2),
                'profit' => round((float) $group->sum(fn ($s) => (float) $s->commission_amount + (float) $s->wage_amount), 2),
            ];
        }

        return $series;
    }
}
