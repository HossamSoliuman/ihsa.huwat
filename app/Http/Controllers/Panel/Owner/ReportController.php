<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Customer;
use App\Models\Fisher;
use App\Models\MonthClosing;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Owner\DalalSettlement;
use App\Services\Owner\OwnerReports;
use App\Support\CsvExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * تقارير المالك (O6): المركز، وكل تقرير بثلاث صيغ من البنية نفسها —
 * صفحة الويب (فلاتر + مؤشرات + مخططات + جداول)، صفحة A4 للطباعة، وملف
 * Excel (CSV بترميز يقرؤه Excel) لجدوله الرئيس.
 */
class ReportController extends Controller
{
    public function __construct(private readonly OwnerReports $reports) {}

    public function index(Request $request, DalalSettlement $settlement): View
    {
        $owner = $request->user();

        return view('panel.owner.reports.index', [
            'reports' => OwnerReports::REPORTS,
            'groups' => OwnerReports::GROUPS,
            'customers' => Customer::forAccount($owner)->orderBy('name')->get(['id', 'name']),
            'vendors' => Vendor::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'fishers' => Fisher::forOwner($owner)->orderBy('name')->get(),
            'dalals' => User::whereIn('id', $settlement->dalalIds($owner))->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, string $report, ?string $mode = null): View|Response
    {
        $owner = $request->user();
        $data = match ($report) {
            'profit-loss' => $this->profitLoss($request, $owner),
            'month-summary' => $this->monthSummary($request, $owner),
            'annual-summary' => $this->annual($request, $owner),
            'expenses-by-category' => $this->expenses($request, $owner),
            'boat-profitability' => $this->boats($request, $owner),
            'trip-profitability' => $this->trips($request, $owner),
            'production' => $this->production($request, $owner),
            'customer-statement' => $this->customer($request, $owner),
            'vendor-statement' => $this->vendor($request, $owner),
        };

        $data += [
            'key' => $report,
            'meta' => OwnerReports::REPORTS[$report],
            'owner' => $owner,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'query' => $request->query(),
        ];

        if ($mode === 'export') {
            abort_if(($data['export'] ?? null) === null, 404);

            return CsvExport::download($this->csvRows($data['export']), 'hawat_'.$report.'_'.($data['file_suffix'] ?? now()->format('Y-m-d')).'.csv');
        }

        if ($mode === 'print') {
            return view($data['print_view'] ?? 'panel.owner.reports.print', $data);
        }

        return view('panel.owner.reports.'.$report, $data);
    }

    // ───────────────────────────── التقارير ─────────────────────────────

    private function profitLoss(Request $request, User $owner): array
    {
        [$from, $to] = $this->monthRange($request, CarbonImmutable::now()->startOfYear());
        $boat = $this->boat($request, $owner);
        $pl = $this->reports->profitLoss($owner, $from, $to, $boat);

        return $pl + [
            'lines' => $this->reports->statementLines($pl),
            'from' => $from,
            'to' => $to,
            'period' => $this->monthsLabel($from, $to),
            'chips' => array_filter(['الفترة' => $this->monthsLabel($from, $to), 'القارب' => $boat?->name ?? 'كل الأسطول']),
            'export' => $this->reports->statementTable($pl),
            'file_suffix' => $from->format('Y-m').'_'.$to->format('Y-m'),
            'print_view' => 'panel.owner.reports.profit-loss-print',
        ];
    }

    private function monthSummary(Request $request, User $owner): array
    {
        $period = $this->monthParam($request->query('period'), CarbonImmutable::now()->startOfMonth());
        $summary = $this->reports->monthSummary($owner, $period->year, $period->month);

        return $summary + [
            'lines' => $this->reports->statementLines($summary),
            'month' => $period,
            'period' => $summary['label'],
            'chips' => [
                'الشهر' => $summary['label'],
                'الحالة' => $summary['status'] === 'closed'
                    ? 'مُغلق — من لقطة الإغلاق'
                    : OwnerReports::MONTH_STATUS[$summary['status']].' — أرقام حيّة قد تتغير حتى يُغلق',
            ],
            'export' => $this->reports->statementTable($summary),
            'file_suffix' => $period->format('Y-m'),
            'print_view' => 'panel.owner.reports.month-summary-print',
        ];
    }

    private function annual(Request $request, User $owner): array
    {
        $year = (int) $request->query('year', (string) now()->year);
        $year = max(2000, min($year, now()->year));
        $annual = $this->reports->annual($owner, $year);
        $first = MonthClosing::forOwner($owner)->min('year');

        return $annual + [
            'period' => (string) $year,
            'years' => range(now()->year, min((int) ($first ?? now()->year), now()->year - 2)),
            'chips' => ['السنة' => (string) $year, 'الأشهر المُغلقة' => $annual['closed'].' من '.$annual['counted']],
            'tables' => [$annual['table']],
            'kpis' => [
                ['label' => 'الإيراد', 'value' => $annual['totals']['revenue'] ?? 0, 'format' => 'money'],
                ['label' => 'مصروفات القوارب', 'value' => $annual['totals']['expenses'] ?? 0, 'format' => 'money'],
                ['label' => 'نصيب الطاقم', 'value' => $annual['totals']['crew_pool'] ?? 0, 'format' => 'money'],
                ['label' => 'صافيك', 'value' => $annual['totals']['owner_net'] ?? 0, 'format' => 'money', 'strong' => true],
            ],
            'export' => $annual['table'],
            'file_suffix' => (string) $year,
            'orientation' => 'landscape',
        ];
    }

    private function expenses(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request, CarbonImmutable::now()->startOfMonth());
        $boat = $request->query('boat_id');
        $boatName = match (true) {
            $boat === 'general' => 'عام (بلا قارب)',
            filled($boat) => $this->boat($request, $owner)?->name,
            default => 'الكل',
        };
        $data = $this->reports->expensesByCategory($owner, $from, $to, filled($boat) ? (string) $boat : null);

        return $data + [
            'from' => $from,
            'to' => $to,
            'period' => $this->daysLabel($from, $to),
            'chips' => ['الفترة' => $this->daysLabel($from, $to), 'القارب' => $boatName],
            'tables' => [$data['groups'], array_merge($data['table'], ['title' => 'حسب الفئة'])],
            'kpis' => [
                ['label' => 'إجمالي المصروفات', 'value' => $data['total'], 'format' => 'money', 'strong' => true],
                ['label' => 'المسدَّد', 'value' => $data['table']['totals']['paid'] ?? 0, 'format' => 'money'],
                ['label' => 'المتبقي', 'value' => $data['table']['totals']['remaining'] ?? 0, 'format' => 'money'],
                ['label' => 'السندات', 'value' => $data['table']['totals']['count'] ?? 0, 'format' => 'int'],
            ],
            'export' => $data['table'],
            'file_suffix' => $from->format('Y-m-d').'_'.$to->format('Y-m-d'),
            'orientation' => 'landscape',
        ];
    }

    private function boats(Request $request, User $owner): array
    {
        [$from, $to] = $this->monthRange($request, CarbonImmutable::now()->startOfYear());
        $data = $this->reports->boatProfitability($owner, $from, $to);
        $t = $data['table']['totals'] ?? [];

        return $data + [
            'from' => $from,
            'to' => $to,
            'period' => $this->monthsLabel($from, $to),
            'chips' => ['الفترة' => $this->monthsLabel($from, $to)],
            'tables' => [$data['table']],
            'kpis' => [
                ['label' => 'صافي الإيراد', 'value' => $t['revenue'] ?? 0, 'format' => 'money'],
                ['label' => 'صافي ربح القوارب', 'value' => $t['net_profit'] ?? 0, 'format' => 'money'],
                ['label' => 'نصيب الطاقم', 'value' => $t['crew_pool'] ?? 0, 'format' => 'money'],
                ['label' => 'نصيبك من القوارب', 'value' => $t['owner_share'] ?? 0, 'format' => 'money'],
                ['label' => 'عام (مصروف + إهلاك)', 'value' => $data['general']['expenses'] + $data['general']['depreciation'], 'format' => 'money'],
                ['label' => 'صافيك', 'value' => $data['owner_net'], 'format' => 'money', 'strong' => true],
            ],
            'export' => $data['table'],
            'file_suffix' => $from->format('Y-m').'_'.$to->format('Y-m'),
            'orientation' => 'landscape',
        ];
    }

    private function trips(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request, CarbonImmutable::now()->startOfYear());
        $boat = $this->boat($request, $owner);
        $data = $this->reports->tripProfitability($owner, $from, $to, $boat?->id);
        $t = $data['table']['totals'] ?? [];

        return $data + [
            'from' => $from,
            'to' => $to,
            'period' => $this->daysLabel($from, $to),
            'chips' => ['المغادرة' => $this->daysLabel($from, $to), 'القارب' => $boat?->name ?? 'كل الأسطول'],
            'tables' => [$data['table']],
            'kpis' => [
                ['label' => 'الرحلات', 'value' => count($data['table']['rows']), 'format' => 'int'],
                ['label' => 'المصيد', 'value' => $t['caught_kg'] ?? 0, 'format' => 'kg'],
                ['label' => 'صافي الإيراد', 'value' => $t['revenue'] ?? 0, 'format' => 'money'],
                ['label' => 'مصروفات الرحلات', 'value' => $t['expenses'] ?? 0, 'format' => 'money'],
                ['label' => 'الربح', 'value' => $t['profit'] ?? 0, 'format' => 'money', 'strong' => true],
            ],
            'export' => $data['table'],
            'file_suffix' => $from->format('Y-m-d').'_'.$to->format('Y-m-d'),
            'orientation' => 'landscape',
        ];
    }

    private function production(Request $request, User $owner): array
    {
        [$from, $to] = $this->dateRange($request, CarbonImmutable::now()->startOfYear());
        $boat = $this->boat($request, $owner);
        $data = $this->reports->production($owner, $from, $to, $boat?->id);
        $t = $data['table']['totals'] ?? [];

        return $data + [
            'from' => $from,
            'to' => $to,
            'period' => $this->daysLabel($from, $to),
            'chips' => ['المغادرة' => $this->daysLabel($from, $to), 'القارب' => $boat?->name ?? 'كل الأسطول', 'الرحلات' => (string) $data['trips']],
            'tables' => [$data['table']],
            'kpis' => [
                ['label' => 'المصيد', 'value' => $t['caught_kg'] ?? 0, 'format' => 'kg'],
                ['label' => 'المباع', 'value' => $t['sold_kg'] ?? 0, 'format' => 'kg'],
                ['label' => 'التصريف', 'value' => $t['sell_through'] ?? null, 'format' => 'pct'],
                ['label' => 'صافيك', 'value' => $t['net'] ?? 0, 'format' => 'money', 'strong' => true],
            ],
            'export' => $data['table'],
            'file_suffix' => $from->format('Y-m-d').'_'.$to->format('Y-m-d'),
            'orientation' => 'landscape',
        ];
    }

    private function customer(Request $request, User $owner): array
    {
        $parties = Customer::forAccount($owner)->orderBy('name')->get(['id', 'name', 'phone']);
        $party = $request->filled('customer_id') ? Customer::forAccount($owner)->findOrFail($request->integer('customer_id')) : null;

        return $this->statementData($request, $owner, $parties, $party, 'customer_id', fn ($from, $to) => $this->reports->customerStatement($owner, $party, $from, $to), 'العميل');
    }

    private function vendor(Request $request, User $owner): array
    {
        $parties = Vendor::forOwner($owner)->orderBy('name')->get(['id', 'name', 'phone']);
        $party = $request->filled('vendor_id') ? Vendor::forOwner($owner)->findOrFail($request->integer('vendor_id')) : null;

        return $this->statementData($request, $owner, $parties, $party, 'vendor_id', fn ($from, $to) => $this->reports->vendorStatement($owner, $party, $from, $to), 'المورد');
    }

    private function statementData(Request $request, User $owner, $parties, $party, string $param, callable $build, string $noun): array
    {
        $from = $this->dateParam($request->query('from'));
        $to = $this->dateParam($request->query('to'));
        $period = ($from ? self::isolate($from) : 'البداية').' — '.($to ? self::isolate($to) : 'اليوم');
        $base = ['parties' => $parties, 'party' => $party, 'param' => $param, 'from' => $from, 'to' => $to, 'period' => $period, 'print_view' => 'panel.owner.reports.statement-print'];

        if ($party === null) {
            abort_if($request->route('mode') !== null, 404);

            return $base + ['statement' => null, 'export' => null];
        }

        $statement = $build($from, $to);

        return $base + [
            'statement' => $statement,
            'chips' => [$noun => $party->name, 'الفترة' => $period],
            'export' => $statement['table'],
            'file_suffix' => $party->id,
        ];
    }

    // ───────────────────────────── الفلاتر ─────────────────────────────

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} أول الشهر الأول ونهاية الأخير
     */
    private function monthRange(Request $request, CarbonImmutable $default): array
    {
        $current = CarbonImmutable::now()->startOfMonth();
        $from = $this->monthParam($request->query('from'), $default);
        $to = $this->monthParam($request->query('to'), $current);

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to->endOfMonth()];
    }

    private function monthParam(mixed $value, CarbonImmutable $default): CarbonImmutable
    {
        $current = CarbonImmutable::now()->startOfMonth();

        if (! is_string($value) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            return $default;
        }

        $month = CarbonImmutable::createFromFormat('!Y-m', $value)->startOfMonth();

        return $month->greaterThan($current) ? $current : $month;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(Request $request, CarbonImmutable $default): array
    {
        $from = CarbonImmutable::parse($this->dateParam($request->query('from')) ?? $default->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($this->dateParam($request->query('to')) ?? now()->toDateString())->endOfDay();

        return $from->greaterThan($to) ? [$to->startOfDay(), $from->endOfDay()] : [$from, $to];
    }

    private function dateParam(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }

    private function boat(Request $request, User $owner): ?Boat
    {
        $id = $request->query('boat_id');

        return filled($id) && $id !== 'general' ? Boat::forOwner($owner)->findOrFail((int) $id) : null;
    }

    private function monthsLabel(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $a = MonthClosing::label($from->year, $from->month);
        $b = MonthClosing::label($to->year, $to->month);

        return $a === $b ? $a : $a.' — '.$b;
    }

    private function daysLabel(CarbonImmutable $from, CarbonImmutable $to): string
    {
        return self::isolate($from->format('Y-m-d')).' — '.self::isolate($to->format('Y-m-d'));
    }

    /**
     * يعزل تاريخًا داخل نص عربي (LRI … PDI): بدونه يُقرأ بعد الحرف العربي
     * أرقامًا عربية فتنقلب مقاطعه (30-09-2026).
     */
    private static function isolate(string $date): string
    {
        return "\u{2066}{$date}\u{2069}";
    }

    /**
     * سطور CSV بعناوين الأعمدة وسطر المجموع — الأرقام خامًا ليحسب بها Excel.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function csvRows(array $table): Collection
    {
        $map = fn (array $row) => collect($table['columns'])->mapWithKeys(fn ($c) => [$c['label'] => $row[$c['key']] ?? ''])->all();
        $rows = collect($table['rows'])->map($map);

        if ($table['totals'] !== null && collect($table['columns'])->contains(fn ($c) => array_key_exists($c['key'], $table['totals']))) {
            $first = $table['columns'][0]['key'];
            $rows->push($map([$first => 'الإجمالي'] + $table['totals']));
        }

        return $rows;
    }
}
