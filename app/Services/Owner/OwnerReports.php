<?php

namespace App\Services\Owner;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\MonthClosing;
use App\Models\Payroll;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Species;
use App\Models\StockMovement;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * تقارير المالك (O6) — ReportsHub / ProfitLoss / MonthSummary / AnnualSummary /
 * ProfitabilityReport / AccountStatement في hispa على بيانات ihsa.
 *
 * الأرقام المالية الشهرية (الإيراد، مصروفات القوارب، الإهلاك المحمَّل ومؤجَّله،
 * نصيب المالك والطاقم، العام، صافي المالك) تُقرأ من إغلاق الشهر: لقطته إن كان
 * مُغلقًا، وإلا معاينته الحيّة بلا تحديث المسيرات ({@see MonthClosingService::preview})
 * — فتتطابق قائمة الأرباح والملخصان وربحية القوارب مع إغلاق كل شهر سطرًا بسطر.
 * التفصيل (أنواع الإيراد، فئات المصروفات، الأصناف، الأوزان) من السجلات مباشرة.
 *
 * كل تقرير بنية واحدة: عنوان، فترة، فلاتر، مؤشرات، وجداول (أعمدة بصيغها،
 * سطور، مجاميع) — تعرضها صفحة الويب وصفحة الطباعة وملف Excel كما هي.
 */
class OwnerReports
{
    public const GROUPS = [
        'finance' => 'التقارير المالية',
        'operations' => 'التشغيل والإنتاج',
        'accounts' => 'كشوف الحساب',
    ];

    public const REPORTS = [
        'profit-loss' => ['title' => 'قائمة الأرباح والخسائر', 'description' => 'الإيراد والمصروفات والإهلاك ونصيب الطاقم وصافيك لفترة من الأشهر', 'icon' => 'scale', 'group' => 'finance', 'tone' => 'primary'],
        'month-summary' => ['title' => 'الملخص الشهري', 'description' => 'قائمة شهر واحد في صفحة: الإيراد، المصروفات بفئاتها، القوارب، الأصناف', 'icon' => 'calendar', 'group' => 'finance', 'tone' => 'info'],
        'annual-summary' => ['title' => 'الملخص السنوي', 'description' => 'السنة شهرًا بشهر من إغلاقات الأشهر، وما لم يُغلق بأرقامه الحيّة', 'icon' => 'calendar-days', 'group' => 'finance', 'tone' => 'success'],
        'expenses-by-category' => ['title' => 'المصروفات حسب الفئة', 'description' => 'مجموع كل فئة ومجموعتها وحصتها والمسدَّد منها', 'icon' => 'receipt', 'group' => 'finance', 'tone' => 'warning'],
        'boat-profitability' => ['title' => 'ربحية القوارب', 'description' => 'إيراد كل قارب ومصروفاته وإهلاكه وربحه ونصيب طاقمه', 'icon' => 'ship', 'group' => 'operations', 'tone' => 'primary'],
        'trip-profitability' => ['title' => 'ربحية الرحلات', 'description' => 'مصيد كل رحلة وما بيع منه وصافيه ومصروفاتها وربحها', 'icon' => 'route', 'group' => 'operations', 'tone' => 'info'],
        'production' => ['title' => 'الإنتاج حسب الصنف', 'description' => 'المصيد والمباع مباشرة وعبر الدلال وسعر الكيلو وما لم يُبع', 'icon' => 'fish', 'group' => 'operations', 'tone' => 'success'],
        'customer-statement' => ['title' => 'كشف حساب عميل', 'description' => 'فواتير العميل والمحصّل منها والرصيد الجاري', 'icon' => 'handshake', 'group' => 'accounts', 'tone' => 'primary'],
        'vendor-statement' => ['title' => 'كشف حساب مورد', 'description' => 'سندات المورد والمسدَّد منها والرصيد الجاري', 'icon' => 'truck', 'group' => 'accounts', 'tone' => 'warning'],
    ];

    public const MONTH_STATUS = ['closed' => 'مُغلق', 'open' => 'غير مُغلق', 'current' => 'الشهر الجاري'];

    /** @var array<string, array<string, mixed>|null> */
    private array $months = [];

    public function __construct(
        private readonly MonthClosingService $closings,
        private readonly CrewPool $pool,
    ) {}

    /**
     * قيمة خلية بصيغة عمودها — واحدة للويب والطباعة.
     */
    public static function format(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($format) {
            'money', 'price' => number_format((float) $value, 2),
            'kg' => number_format((float) $value, 1),
            'int' => number_format((float) $value),
            'pct' => number_format((float) $value, 1).'%',
            default => (string) $value,
        };
    }

    public static function isNumeric(string $format): bool
    {
        return in_array($format, ['money', 'price', 'kg', 'int', 'pct'], true);
    }

    // ───────────────────────────── الأشهر ─────────────────────────────

    /**
     * أرقام شهر كما في إغلاقه: لقطته إن كان مُغلقًا، وإلا المعاينة الحيّة.
     * null لشهر لم يبدأ.
     *
     * @return array{status: string, closing: ?MonthClosing, data: array<string, mixed>}|null
     */
    public function month(User $owner, int $year, int $month): ?array
    {
        $key = sprintf('%d-%04d-%02d', $owner->id, $year, $month);

        if (array_key_exists($key, $this->months)) {
            return $this->months[$key];
        }

        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        if ($start->greaterThan(CarbonImmutable::now()->startOfMonth())) {
            return $this->months[$key] = null;
        }

        $closing = MonthClosing::forOwner($owner)->where('year', $year)->where('month', $month)->first();
        $data = $closing ? $this->closings->present($closing) : $this->closings->preview($owner, $year, $month, false);

        // شهر قبل أول إغلاق لن يُغلق أبدًا (الإغلاق بالتسلسل بعده)، فرواتبه
        // الثابتة التي "ستُرحَّل" لن تُرحَّل: تُحسب أرقام قواربه بلاها.
        $firstClosed = $closing ? null : MonthClosing::forOwner($owner)->orderBy('year')->orderBy('month')->first();

        if ($firstClosed && $start->lessThan($firstClosed->period_start)) {
            $data['boats'] = array_map(function (array $b) use ($owner, $year, $month) {
                if ($b['payroll'] !== null || $b['pending_fixed'] <= 0) {
                    return $b;
                }

                return array_merge($b, $this->pool->forBoatMonth($owner, $b['boat_id'], $year, $month, (float) $b['owner_share_percent'], (float) $b['depreciation_brought_forward']), ['pending_fixed' => 0.0]);
            }, $data['boats']);
        }

        // المعاينة تُظهر القارب برواتب أفراده الثابتة وإن لم يكن له في الشهر
        // بيع ولا سند ولا إهلاك (شهر قبل بدء النشاط) — لا يعدّه التقرير نشاطًا.
        $data['boats'] = array_values(array_filter($data['boats'], fn (array $b) => $closing !== null
            || $b['payroll'] !== null
            || $b['revenue'] != 0
            || round($b['expenses'] - $b['pending_fixed'], 2) != 0
            || $b['depreciation_own'] != 0
            || $b['depreciation_brought_forward'] != 0));

        return $this->months[$key] = [
            'status' => $closing ? 'closed' : ($start->endOfMonth()->isPast() ? 'open' : 'current'),
            'closing' => $closing,
            'data' => $data,
        ];
    }

    /**
     * مجاميع الشهر (للأسطول أو لقارب واحد — والقارب لا عام عليه).
     *
     * @return array<string, float>
     */
    public function figures(array $month, ?int $boatId = null): array
    {
        $boats = collect($month['data']['boats'])->when($boatId, fn ($c) => $c->where('boat_id', $boatId));
        $sum = fn (string $key) => round((float) $boats->sum($key), 2);

        $f = [];
        foreach (['revenue', 'expenses', 'pending_fixed', 'depreciation_own', 'depreciation_brought_forward', 'depreciation_charged', 'depreciation_deferred', 'net_profit', 'owner_share', 'crew_pool'] as $key) {
            $f[$key] = $sum($key);
        }

        $f['general_expenses'] = $boatId ? 0.0 : round((float) $month['data']['general']['expenses'], 2);
        $f['general_depreciation'] = $boatId ? 0.0 : round((float) $month['data']['general']['depreciation'], 2);
        $f['owner_net'] = round($f['owner_share'] - $f['general_expenses'] - $f['general_depreciation'], 2);

        return $f;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    public static function monthsBetween(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = [];

        for ($m = $from->startOfMonth(); $m->lessThanOrEqualTo($to); $m = $m->addMonth()) {
            $months[] = $m;
        }

        return $months;
    }

    // ─────────────────────────── قائمة الأرباح ───────────────────────────

    /**
     * قائمة الأرباح والخسائر لأشهر متتالية (وقارب واحد إن طُلب).
     *
     * @return array<string, mixed>
     */
    public function profitLoss(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?Boat $boat = null): array
    {
        $boatId = $boat?->id;
        $months = [];
        $keys = ['revenue', 'expenses', 'pending_fixed', 'depreciation_own', 'depreciation_brought_forward', 'depreciation_charged', 'net_profit', 'owner_share', 'crew_pool', 'general_expenses', 'general_depreciation', 'owner_net'];
        $sum = array_fill_keys($keys, 0.0);
        $lastDeferred = 0.0;
        $statuses = [];

        foreach (self::monthsBetween($from, $to) as $m) {
            $month = $this->month($owner, $m->year, $m->month);

            if ($month === null) {
                continue;
            }

            $f = $this->figures($month, $boatId);
            foreach ($keys as $key) {
                $sum[$key] = round($sum[$key] + $f[$key], 2);
            }
            $lastDeferred = $f['depreciation_deferred'];
            $statuses[] = $month['status'];

            $months[] = [
                'month' => MonthClosing::label($m->year, $m->month),
                'status' => self::MONTH_STATUS[$month['status']],
                'revenue' => $f['revenue'],
                'expenses' => round($f['expenses'] + $f['general_expenses'], 2),
                'depreciation' => round($f['depreciation_charged'] + $f['general_depreciation'], 2),
                'operating' => round($f['net_profit'] - $f['general_expenses'] - $f['general_depreciation'], 2),
                'crew_pool' => $f['crew_pool'],
                'owner_net' => $f['owner_net'],
                '_closed' => $month['status'] === 'closed',
            ];
        }

        $start = $from->startOfMonth();
        $end = $to->endOfMonth();
        $revenue = $this->revenueSplit($owner, $start, $end, $boatId);
        $expenses = $this->expenseCategories($owner, $start, $end, $boatId === null ? null : (string) $boatId);

        $totalExpenses = round($sum['expenses'] + $sum['general_expenses'], 2);
        $operating = round($sum['revenue'] - $totalExpenses - $sum['depreciation_charged'] - $sum['general_depreciation'], 2);

        $open = count(array_filter($statuses, fn ($s) => $s !== 'closed'));

        return [
            'figures' => $sum + [
                'total_expenses' => $totalExpenses,
                'depreciation_deferred' => $lastDeferred,
                'operating' => $operating,
                'margin' => $sum['revenue'] > 0 ? round($operating / $sum['revenue'] * 100, 1) : null,
            ],
            'revenue' => $revenue,
            'expense_groups' => $expenses['groups'],
            'months' => $this->table([
                $this->col('month', 'الشهر'),
                $this->col('status', 'الحالة'),
                $this->col('revenue', 'الإيراد', 'money'),
                $this->col('expenses', 'المصروفات', 'money'),
                $this->col('depreciation', 'الإهلاك المحمَّل', 'money'),
                $this->col('operating', 'الربح التشغيلي', 'money', strong: true),
                $this->col('crew_pool', 'نصيب الطاقم', 'money'),
                $this->col('owner_net', 'صافيك', 'money', strong: true),
            ], $months, title: 'شهرًا بشهر'),
            'closed_count' => count($statuses) - $open,
            'open_count' => $open,
            'boat' => $boat,
            'notes' => array_values(array_filter([
                'الإيراد صافيك من البيع: بيعك المباشر كاملًا، وبيع الدلال بعد عمولته وأجور العمالة.',
                'الإهلاك المحمَّل ما غطّاه ربح القارب من قسط أصوله (والمؤجَّل من الشهر السابق)، والباقي يؤجَّل — قاعدة إغلاق الشهر.',
                'نصيب الطاقم توزيع للربح بالنِّسب والأسهم لا مصروف؛ الرواتب الثابتة داخل المصروفات.',
                match (true) {
                    $open === 0 => null,
                    count($statuses) === 1 => 'الشهر غير مُغلق — أرقامه حيّة وقد تتغير حتى يُغلق.',
                    default => "{$open} من أشهر الفترة غير مُغلق — أرقامها حيّة وقد تتغير حتى تُغلق.",
                },
                $sum['pending_fixed'] > 0 ? 'المصروفات تشمل رواتب ثابتة لم تُرحَّل بعد ('.number_format($sum['pending_fixed'], 2).' ر.س) — تُرحَّل عند إنشاء مسير الشهر.' : null,
            ])),
        ];
    }

    /**
     * سطور القائمة للتصدير: بند ومبلغ.
     *
     * @return array<string, mixed>
     */
    public function statementTable(array $pl): array
    {
        $rows = array_map(fn (array $line) => [
            'item' => in_array($line['type'], ['sub'], true) ? '   '.$line['label'] : $line['label'],
            'amount' => $line['amount'],
            'hint' => $line['hint'] ?? null,
        ], array_filter($this->statementLines($pl), fn ($line) => $line['type'] !== 'hd'));

        return $this->table([$this->col('item', 'البند'), $this->col('amount', 'المبلغ', 'money', sum: false), $this->col('hint', 'ملاحظة')], array_values($rows));
    }

    /**
     * بنود القائمة بالترتيب: hd عنوان قسم، row بند، sub بند فرعي، total مجموع
     * قسم، grand نتيجة. المبالغ المطروحة سالبة. إن خالف تفصيلُ السجلات لقطةَ
     * الإغلاق يظهر الفرق بندًا مستقلًا فلا يختفي.
     *
     * @return array<int, array{type: string, label: string, amount: ?float, hint?: ?string}>
     */
    public function statementLines(array $pl): array
    {
        $f = $pl['figures'];
        $r = $pl['revenue'];
        $money = fn (float $v) => number_format($v, 2);

        $lines = [
            ['type' => 'hd', 'label' => 'الإيراد', 'amount' => null],
            ['type' => 'row', 'label' => 'مبيعات مباشرة', 'amount' => $r['direct_net'], 'hint' => 'بيعك لمصيد رحلاتك'],
            ['type' => 'row', 'label' => 'مبيعات الدلالين (الإجمالي)', 'amount' => $r['dalal_gross'], 'hint' => 'سطور مصيدك في فواتيرهم'],
            ['type' => 'row', 'label' => 'عمولة الدلالين وأجور العمالة', 'amount' => -$r['dalal_cut']],
        ];
        $diff = round($f['revenue'] - $r['net'], 2);
        if (abs($diff) >= 0.01) {
            $lines[] = ['type' => 'row', 'label' => 'فرق عن لقطة الإغلاق', 'amount' => $diff, 'hint' => 'سجلات تغيّرت بعد إغلاق الشهر'];
        }
        $lines[] = ['type' => 'total', 'label' => 'صافي الإيراد', 'amount' => $f['revenue']];

        $lines[] = ['type' => 'hd', 'label' => 'المصروفات', 'amount' => null];
        $listed = 0.0;
        foreach ($pl['expense_groups'] as $group) {
            $lines[] = ['type' => 'row', 'label' => $group['name'], 'amount' => -$group['total']];
            foreach ($group['categories'] as $category) {
                $lines[] = ['type' => 'sub', 'label' => $category['name'], 'amount' => -$category['total'], 'hint' => $category['count'].' سند'];
            }
            $listed += $group['total'];
        }
        if ($f['pending_fixed'] > 0) {
            $lines[] = ['type' => 'row', 'label' => 'رواتب ثابتة لم تُرحَّل بعد', 'amount' => -$f['pending_fixed'], 'hint' => 'تُرحَّل عند إنشاء مسير الشهر'];
            $listed += $f['pending_fixed'];
        }
        $diff = round($f['total_expenses'] - $listed, 2);
        if (abs($diff) >= 0.01) {
            $lines[] = ['type' => 'row', 'label' => 'فرق عن لقطة الإغلاق', 'amount' => -$diff, 'hint' => 'سندات قارب لم يدخل إغلاق الشهر'];
        }
        $lines[] = ['type' => 'total', 'label' => 'إجمالي المصروفات', 'amount' => -$f['total_expenses']];

        $lines[] = ['type' => 'hd', 'label' => 'الإهلاك', 'amount' => null];
        $lines[] = ['type' => 'row', 'label' => 'إهلاك أصول القوارب المحمَّل', 'amount' => -$f['depreciation_charged'],
            'hint' => 'القسط '.$money($f['depreciation_own']).($f['depreciation_brought_forward'] > 0 ? ' + مؤجَّل داخل '.$money($f['depreciation_brought_forward']) : '').($f['depreciation_deferred'] > 0 ? ' — مؤجَّل لما بعد الفترة '.$money($f['depreciation_deferred']) : ''),
        ];
        if (($pl['boat'] ?? null) === null) {
            $lines[] = ['type' => 'row', 'label' => 'إهلاك الأصول العامة', 'amount' => -$f['general_depreciation'], 'hint' => 'أصول غير مربوطة بقارب'];
        }
        $lines[] = ['type' => 'total', 'label' => 'إجمالي الإهلاك', 'amount' => -round($f['depreciation_charged'] + $f['general_depreciation'], 2)];

        $lines[] = ['type' => 'grand', 'label' => 'الربح التشغيلي', 'amount' => $f['operating'], 'hint' => $f['margin'] !== null ? 'هامش '.($f['margin'] < 0 ? 'سالب ' : '').number_format(abs($f['margin']), 1).'% من صافي الإيراد' : null];

        $lines[] = ['type' => 'hd', 'label' => 'توزيع الربح', 'amount' => null];
        $lines[] = ['type' => 'row', 'label' => 'نصيب الطاقم من أرباح القوارب', 'amount' => -$f['crew_pool'], 'hint' => 'بالنِّسب والأسهم — مسيرات الرواتب'];
        $lines[] = ['type' => 'grand', 'label' => ($pl['boat'] ?? null) ? 'نصيبك من القارب' : 'صافي المالك', 'amount' => $f['owner_net']];

        return $lines;
    }

    // ─────────────────────────── الملخص الشهري ───────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function monthSummary(User $owner, int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $pl = $this->profitLoss($owner, $start, $start->endOfMonth());
        $data = $this->month($owner, $year, $month);

        $boats = collect($data['data']['boats'])->map(fn (array $b) => [
            'boat' => $b['boat_name'],
            'revenue' => (float) $b['revenue'],
            'expenses' => (float) $b['expenses'],
            'depreciation' => (float) $b['depreciation_charged'],
            'deferred' => (float) $b['depreciation_deferred'],
            'net_profit' => (float) $b['net_profit'],
            'owner_percent' => (float) $b['owner_share_percent'],
            'owner_share' => (float) $b['owner_share'],
            'crew_pool' => (float) $b['crew_pool'],
            'payroll' => $b['payroll']?->payroll_number ?? '—',
        ])->values()->all();

        $species = collect($this->speciesSold($owner, $start, $start->endOfMonth()))->sortByDesc('net')->values()->all();

        return $pl + [
            'label' => MonthClosing::label($year, $month),
            'status' => $data['status'],
            'closing' => $data['closing'],
            'boats' => $this->table([
                $this->col('boat', 'القارب'),
                $this->col('revenue', 'الإيراد', 'money'),
                $this->col('expenses', 'المصروفات', 'money'),
                $this->col('depreciation', 'الإهلاك المحمَّل', 'money'),
                $this->col('deferred', 'مؤجَّل للتالي', 'money'),
                $this->col('net_profit', 'صافي الربح', 'money', strong: true),
                $this->col('owner_percent', 'نسبتك', 'pct', sum: false),
                $this->col('owner_share', 'نصيبك', 'money'),
                $this->col('crew_pool', 'نصيب الطاقم', 'money'),
                $this->col('payroll', 'المسير'),
            ], $boats, title: 'القوارب'),
            'species' => $this->table([
                $this->col('species', 'الصنف'),
                $this->col('kg', 'المباع', 'kg'),
                $this->col('gross', 'قيمة البيع', 'money'),
                $this->col('cut', 'العمولة والأجور', 'money'),
                $this->col('net', 'صافيك', 'money', strong: true),
                $this->col('avg_price', 'سعر الكيلو', 'price', sum: false),
            ], $species, ['avg_price' => $this->ratio(array_sum(array_column($species, 'gross')), array_sum(array_column($species, 'kg')))], title: 'المبيعات حسب الصنف'),
        ];
    }

    // ─────────────────────────── الملخص السنوي ───────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function annual(User $owner, int $year): array
    {
        $rows = [];
        $closed = 0;
        $counted = 0;

        for ($m = 1; $m <= 12; $m++) {
            $month = $this->month($owner, $year, $m);
            $row = ['month' => Payroll::MONTHS[$m], 'status' => 'لم يبدأ', '_dim' => true];

            if ($month !== null) {
                $f = $this->figures($month);
                $counted++;
                $closed += $month['status'] === 'closed' ? 1 : 0;
                $row = [
                    'month' => Payroll::MONTHS[$m],
                    'status' => self::MONTH_STATUS[$month['status']],
                    'revenue' => $f['revenue'],
                    'expenses' => $f['expenses'],
                    'depreciation' => $f['depreciation_charged'],
                    'deferred' => $f['depreciation_deferred'],
                    'net_profit' => $f['net_profit'],
                    'crew_pool' => $f['crew_pool'],
                    'owner_share' => $f['owner_share'],
                    'general' => round($f['general_expenses'] + $f['general_depreciation'], 2),
                    'owner_net' => $f['owner_net'],
                    '_closed' => $month['status'] === 'closed',
                ];
            }

            $rows[] = $row;
        }

        $table = $this->table([
            $this->col('month', 'الشهر'),
            $this->col('status', 'الحالة'),
            $this->col('revenue', 'الإيراد', 'money'),
            $this->col('expenses', 'مصروفات القوارب', 'money'),
            $this->col('depreciation', 'الإهلاك المحمَّل', 'money'),
            $this->col('deferred', 'مؤجَّل', 'money', sum: false),
            $this->col('net_profit', 'صافي ربح القوارب', 'money'),
            $this->col('crew_pool', 'نصيب الطاقم', 'money'),
            $this->col('owner_share', 'نصيبك', 'money'),
            $this->col('general', 'عام (مصروف + إهلاك)', 'money'),
            $this->col('owner_net', 'صافيك', 'money', strong: true),
        ], $rows);

        $t = $table['totals'];
        $lastDeferred = collect($rows)->whereNotNull('deferred')->last()['deferred'] ?? 0.0;
        $table['totals']['deferred'] = $lastDeferred;

        return [
            'year' => $year,
            'table' => $table,
            'closed' => $closed,
            'counted' => $counted,
            'totals' => $t,
            'margin' => ($t['revenue'] ?? 0) > 0 ? round($t['owner_net'] / $t['revenue'] * 100, 1) : null,
            'chart' => [
                'labels' => array_column($rows, 'month'),
                'revenue' => array_map(fn ($r) => $r['revenue'] ?? null, $rows),
                'costs' => array_map(fn ($r) => isset($r['revenue']) ? round($r['expenses'] + $r['depreciation'] + $r['general'], 2) : null, $rows),
                'owner_net' => array_map(fn ($r) => $r['owner_net'] ?? null, $rows),
            ],
            'notes' => array_values(array_filter([
                "الأشهر المُغلقة من لقطة إغلاقها ({$closed} من {$counted})؛ وما لم يُغلق بأرقامه الحيّة كما في معاينة إغلاقه.",
                'المؤجَّل في المجموع هو مؤجَّل آخر شهر — ينتقل إلى الشهر التالي ولا يُجمع.',
                'صافيك = نصيبك من القوارب − المصروفات العامة − إهلاك الأصول العامة.',
            ])),
        ];
    }

    // ─────────────────────────── ربحية القوارب ───────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function boatProfitability(User $owner, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $start = $from->startOfMonth();
        $end = $to->endOfMonth();
        $acc = [];
        $general = ['expenses' => 0.0, 'depreciation' => 0.0];

        foreach (self::monthsBetween($start, $end) as $m) {
            $month = $this->month($owner, $m->year, $m->month);

            if ($month === null) {
                continue;
            }

            foreach ($month['data']['boats'] as $b) {
                $row = $acc[$b['boat_id']] ?? ['boat_id' => $b['boat_id'], 'boat' => $b['boat_name']] + array_fill_keys(['revenue', 'expenses', 'depreciation', 'net_profit', 'crew_pool', 'owner_share'], 0.0);
                $row['revenue'] += $b['revenue'];
                $row['expenses'] += $b['expenses'];
                $row['depreciation'] += $b['depreciation_charged'];
                $row['net_profit'] += $b['net_profit'];
                $row['crew_pool'] += $b['crew_pool'];
                $row['owner_share'] += $b['owner_share'];
                $acc[$b['boat_id']] = $row;
            }

            $general['expenses'] += $month['data']['general']['expenses'];
            $general['depreciation'] += $month['data']['general']['depreciation'];
        }

        $trips = Trip::forOwner($owner)->where('status', '!=', Trip::CANCELLED)
            ->whereBetween('departure_time', [$start, $end])->get(['id', 'boat_id']);
        $caught = $this->caughtByTrip($trips->pluck('id'));
        $sales = $this->salesByTrip($owner, $start, $end);
        $boatOf = Trip::whereIn('id', $sales->keys())->pluck('boat_id', 'id');

        $rows = collect($acc)->map(function (array $row) use ($trips, $caught, $sales, $boatOf) {
            $boatTrips = $trips->where('boat_id', $row['boat_id'])->pluck('id');
            $boatSales = $sales->filter(fn ($s, $tripId) => (int) ($boatOf[$tripId] ?? 0) === $row['boat_id']);

            foreach (['revenue', 'expenses', 'depreciation', 'net_profit', 'crew_pool', 'owner_share'] as $k) {
                $row[$k] = round($row[$k], 2);
            }

            return [
                'boat' => $row['boat'],
                'trips' => $boatTrips->count(),
                'caught_kg' => round($boatTrips->sum(fn ($id) => $caught[$id] ?? 0), 2),
                'sold_kg' => round($boatSales->sum(fn ($s) => $s['direct_kg'] + $s['dalal_kg']), 2),
                'gross' => round($boatSales->sum(fn ($s) => $s['direct_gross'] + $s['dalal_gross']), 2),
                'cut' => round($boatSales->sum('dalal_cut'), 2),
                'revenue' => $row['revenue'],
                'expenses' => $row['expenses'],
                'depreciation' => $row['depreciation'],
                'net_profit' => $row['net_profit'],
                'margin' => $this->percent($row['net_profit'], $row['revenue']),
                'crew_pool' => $row['crew_pool'],
                'owner_share' => $row['owner_share'],
            ];
        })->sortByDesc('net_profit')->values()->all();

        $table = $this->table([
            $this->col('boat', 'القارب'),
            $this->col('trips', 'الرحلات', 'int'),
            $this->col('caught_kg', 'المصيد', 'kg'),
            $this->col('sold_kg', 'المباع', 'kg'),
            $this->col('gross', 'قيمة البيع', 'money'),
            $this->col('cut', 'اقتطاع الدلال', 'money'),
            $this->col('revenue', 'صافي الإيراد', 'money'),
            $this->col('expenses', 'المصروفات', 'money'),
            $this->col('depreciation', 'الإهلاك', 'money'),
            $this->col('net_profit', 'صافي الربح', 'money', strong: true),
            $this->col('margin', 'الهامش', 'pct', sum: false),
            $this->col('crew_pool', 'نصيب الطاقم', 'money'),
            $this->col('owner_share', 'نصيبك', 'money'),
        ], $rows);

        if ($table['totals'] !== null) {
            $table['totals']['margin'] = $this->percent($table['totals']['net_profit'], $table['totals']['revenue']);
        }

        $ownerShare = (float) ($table['totals']['owner_share'] ?? 0);
        $general = array_map(fn ($v) => round($v, 2), $general);

        return [
            'table' => $table,
            'general' => $general,
            'owner_net' => round($ownerShare - $general['expenses'] - $general['depreciation'], 2),
            'chart' => [
                'labels' => array_column($rows, 'boat'),
                'revenue' => array_column($rows, 'revenue'),
                'costs' => array_map(fn ($r) => round($r['expenses'] + $r['depreciation'], 2), $rows),
                'net' => array_column($rows, 'net_profit'),
            ],
            'notes' => [
                'الإيراد والمصروفات والإهلاك ونصيب الطاقم من إغلاق كل شهر (أو معاينته إن لم يُغلق) — فمجموعها يطابق الملخص السنوي وقائمة الأرباح.',
                'الرحلات والمصيد: ما غادر في الفترة. المباع وقيمته: ما بيع في الفترة من أي رحلة للقارب.',
                'الهامش = صافي الربح ÷ صافي الإيراد. المصروفات العامة وإهلاك الأصول غير المربوطة لا تُحمَّل على قارب.',
            ],
        ];
    }

    // ─────────────────────────── ربحية الرحلات ───────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function tripProfitability(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId = null): array
    {
        $trips = Trip::forOwner($owner)->where('status', '!=', Trip::CANCELLED)
            ->whereBetween('departure_time', [$from->startOfDay(), $to->endOfDay()])
            ->when($boatId, fn ($q) => $q->where('boat_id', $boatId))
            ->with(['boat:id,name', 'captain:id,name'])
            ->orderBy('departure_time')->get();

        $ids = $trips->pluck('id');
        $caught = $this->caughtByTrip($ids);
        $sales = $this->salesByTrip($owner, null, null, $ids);
        $expenses = Expense::forOwner($owner)->whereIn('trip_id', $ids)
            ->selectRaw('trip_id, SUM(total) AS total')->groupBy('trip_id')->pluck('total', 'trip_id');
        $unsold = StockMovement::whereIn('trip_id', $ids)
            ->selectRaw('trip_id, SUM(weight_kg) AS kg')->groupBy('trip_id')->pluck('kg', 'trip_id');

        $rows = $trips->map(function (Trip $trip) use ($caught, $sales, $expenses, $unsold) {
            $s = $sales[$trip->id] ?? $this->emptySales();
            $net = round($s['direct_net'] + $s['dalal_net'], 2);
            $cost = round((float) ($expenses[$trip->id] ?? 0), 2);

            return [
                'trip' => $trip->trip_number,
                'date' => $trip->departure_time?->format('Y-m-d'),
                'boat' => $trip->boat?->name ?? '—',
                'captain' => $trip->captain?->name ?? $trip->captain_name ?? '—',
                'sale_status' => $trip->sale_status,
                'caught_kg' => round((float) ($caught[$trip->id] ?? 0), 2),
                'sold_kg' => round($s['direct_kg'] + $s['dalal_kg'], 2),
                'unsold_kg' => round(max((float) ($unsold[$trip->id] ?? 0), 0), 2),
                'gross' => round($s['direct_gross'] + $s['dalal_gross'], 2),
                'cut' => round($s['dalal_cut'], 2),
                'revenue' => $net,
                'expenses' => $cost,
                'profit' => round($net - $cost, 2),
                'margin' => $this->percent($net - $cost, $net),
                '_url' => route('panel.owner.trips.show', $trip->id),
            ];
        })->all();

        $table = $this->table([
            $this->col('trip', 'الرحلة', 'code'),
            $this->col('date', 'المغادرة', 'date'),
            $this->col('boat', 'القارب'),
            $this->col('captain', 'الكابتن'),
            $this->col('sale_status', 'البيع'),
            $this->col('caught_kg', 'المصيد', 'kg'),
            $this->col('sold_kg', 'المباع', 'kg'),
            $this->col('unsold_kg', 'لم يُبع', 'kg'),
            $this->col('gross', 'قيمة البيع', 'money'),
            $this->col('cut', 'اقتطاع الدلال', 'money'),
            $this->col('revenue', 'صافي الإيراد', 'money'),
            $this->col('expenses', 'مصروفات الرحلة', 'money'),
            $this->col('profit', 'الربح', 'money', strong: true),
            $this->col('margin', 'الهامش', 'pct', sum: false),
        ], $rows);

        if ($table['totals'] !== null) {
            $table['totals']['margin'] = $this->percent($table['totals']['profit'], $table['totals']['revenue']);
        }

        return [
            'table' => $table,
            'notes' => [
                'الرحلات التي غادرت في الفترة (عدا الملغاة)، وإيرادها كل ما بيع من مصيدها في أي تاريخ: بيعك المباشر كاملًا وبيع الدلال بعد العمولة والأجور.',
                'مصروفات الرحلة: السندات المربوطة بها وحدها. مصروفات القارب العامة والإهلاك تظهر في ربحية القوارب.',
                'لم يُبع: ما بقي من مصيد الرحلة في مخزونك أو عند الدلالين.',
            ],
        ];
    }

    // ─────────────────────────── الإنتاج حسب الصنف ───────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function production(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId = null): array
    {
        $trips = Trip::forOwner($owner)->where('status', '!=', Trip::CANCELLED)
            ->whereBetween('departure_time', [$from->startOfDay(), $to->endOfDay()])
            ->when($boatId, fn ($q) => $q->where('boat_id', $boatId))
            ->pluck('id');

        $caught = CatchRecord::whereIn('trip_id', $trips)
            ->selectRaw('species_id, COUNT(DISTINCT trip_id) AS trips, SUM(COALESCE(counted_kg, quantity_kg)) AS kg')
            ->groupBy('species_id')->get()->keyBy('species_id');
        $sold = collect($this->speciesSold($owner, null, null, $trips))->keyBy('species_id');
        $unsold = StockMovement::whereIn('trip_id', $trips)
            ->selectRaw('species_id, SUM(weight_kg) AS kg')->groupBy('species_id')->pluck('kg', 'species_id');

        $names = Species::whereIn('id', $caught->keys()->merge($sold->keys())->unique())->pluck('name_ar', 'id');

        $rows = $names->map(function ($name, $id) use ($caught, $sold, $unsold) {
            $s = $sold[$id] ?? ['direct_kg' => 0.0, 'dalal_kg' => 0.0, 'kg' => 0.0, 'gross' => 0.0, 'cut' => 0.0, 'net' => 0.0];
            $caughtKg = round((float) ($caught[$id]->kg ?? 0), 2);

            return [
                'species' => $name,
                'trips' => (int) ($caught[$id]->trips ?? 0),
                'caught_kg' => $caughtKg,
                'direct_kg' => $s['direct_kg'],
                'dalal_kg' => $s['dalal_kg'],
                'sold_kg' => $s['kg'],
                'sell_through' => $this->percent($s['kg'], $caughtKg),
                'unsold_kg' => round(max((float) ($unsold[$id] ?? 0), 0), 2),
                'gross' => $s['gross'],
                'cut' => $s['cut'],
                'net' => $s['net'],
                'avg_price' => $this->ratio($s['gross'], $s['kg']),
            ];
        })->sortByDesc('caught_kg')->values()->all();

        $table = $this->table([
            $this->col('species', 'الصنف'),
            $this->col('trips', 'الرحلات', 'int', sum: false),
            $this->col('caught_kg', 'المصيد', 'kg'),
            $this->col('direct_kg', 'بيع مباشر', 'kg'),
            $this->col('dalal_kg', 'عبر الدلال', 'kg'),
            $this->col('sold_kg', 'المباع', 'kg'),
            $this->col('sell_through', 'التصريف', 'pct', sum: false),
            $this->col('unsold_kg', 'لم يُبع', 'kg'),
            $this->col('gross', 'قيمة البيع', 'money'),
            $this->col('cut', 'اقتطاع الدلال', 'money'),
            $this->col('net', 'صافيك', 'money', strong: true),
            $this->col('avg_price', 'سعر الكيلو', 'price', sum: false),
        ], $rows);

        if ($table['totals'] !== null) {
            $table['totals']['trips'] = $trips->count();
            $table['totals']['sell_through'] = $this->percent($table['totals']['sold_kg'], $table['totals']['caught_kg']);
            $table['totals']['avg_price'] = $this->ratio($table['totals']['gross'], $table['totals']['sold_kg']);
        }

        $top = array_slice($rows, 0, 10);

        return [
            'table' => $table,
            'trips' => $trips->count(),
            'chart' => [
                'labels' => array_column($top, 'species'),
                'caught' => array_column($top, 'caught_kg'),
                'sold' => array_column($top, 'sold_kg'),
            ],
            'notes' => [
                'مصيد الرحلات التي غادرت في الفترة (عدا الملغاة) — الوزن المعدود، وإلا ما أعلنه الكابتن — وما بيع منه في أي تاريخ.',
                'قيمة البيع المباشر بعد توزيع خصم الفاتورة على سطورها؛ صافيك من الدلال بعد عمولته والأجور. سعر الكيلو = قيمة البيع ÷ المباع.',
            ],
        ];
    }

    // ─────────────────────────── المصروفات حسب الفئة ───────────────────────────

    /**
     * @param  string|null  $boat  رقم قارب، أو "general" لما لا قارب له، أو null للكل
     * @return array<string, mixed>
     */
    public function expensesByCategory(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?string $boat = null): array
    {
        $data = $this->expenseCategories($owner, $from->startOfDay(), $to->endOfDay(), $boat);
        $total = $data['total'];

        $rows = [];
        foreach ($data['groups'] as $group) {
            foreach ($group['categories'] as $c) {
                $rows[] = ['group' => $group['name'], 'category' => $c['name']] + $c + ['share' => $this->percent($c['total'], $total)];
            }
        }

        $table = $this->table([
            $this->col('group', 'المجموعة'),
            $this->col('category', 'الفئة'),
            $this->col('count', 'السندات', 'int'),
            $this->col('subtotal', 'المبلغ', 'money'),
            $this->col('discount', 'الخصم', 'money'),
            $this->col('vat', 'الضريبة', 'money'),
            $this->col('total', 'الإجمالي', 'money', strong: true),
            $this->col('paid', 'المسدَّد', 'money'),
            $this->col('remaining', 'المتبقي', 'money'),
            $this->col('share', 'الحصة', 'pct', sum: false),
        ], $rows, $rows ? ['share' => 100.0] : []);

        $groups = array_map(fn ($g) => [
            'group' => $g['name'],
            'count' => array_sum(array_column($g['categories'], 'count')),
            'total' => $g['total'],
            'paid' => round(array_sum(array_column($g['categories'], 'paid')), 2),
            'remaining' => round(array_sum(array_column($g['categories'], 'remaining')), 2),
            'share' => $this->percent($g['total'], $total),
        ], $data['groups']);

        return [
            'table' => $table,
            'groups' => $this->table([
                $this->col('group', 'المجموعة'),
                $this->col('count', 'السندات', 'int'),
                $this->col('total', 'الإجمالي', 'money', strong: true),
                $this->col('paid', 'المسدَّد', 'money'),
                $this->col('remaining', 'المتبقي', 'money'),
                $this->col('share', 'الحصة', 'pct', sum: false),
            ], $groups, $groups ? ['share' => 100.0] : [], title: 'حسب المجموعة'),
            'total' => $total,
            'chart' => [
                'groups' => array_column($groups, 'group'),
                'group_totals' => array_column($groups, 'total'),
                'categories' => array_column(collect($rows)->sortByDesc('total')->take(10)->values()->all(), 'category'),
                'category_totals' => array_column(collect($rows)->sortByDesc('total')->take(10)->values()->all(), 'total'),
            ],
            'notes' => [
                'الإجمالي = (المبلغ − الخصم) + الضريبة، والحصة من إجمالي الفترة. التاريخ تاريخ السند.',
            ],
        ];
    }

    // ─────────────────────────── كشوف الحساب ───────────────────────────

    /**
     * كشف حساب عميل: فواتير المالك له بالتاريخ، والمحصّل منها، ورصيد جارٍ
     * للمتبقي، ورصيد افتتاحي لما قبل الفترة.
     *
     * @return array<string, mixed>
     */
    public function customerStatement(User $owner, Customer $customer, ?string $from, ?string $to): array
    {
        $sales = Sale::forSeller($owner)->where('customer_id', $customer->id)
            ->with('trip:id,trip_number')->withSum('items', 'weight_kg')
            ->orderBy('sold_at')->orderBy('id')->get();

        $entries = $sales->map(fn (Sale $sale) => [
            'date' => $sale->sold_at->format('Y-m-d'),
            'number' => $sale->invoice_number,
            'details' => trim(($sale->trip ? 'رحلة '.$sale->trip->trip_number : '').($sale->notes ? ' — '.$sale->notes : ''), ' —') ?: '—',
            'kg' => round((float) $sale->items_sum_weight_kg, 2),
            'debit' => round((float) $sale->total, 2),
            'credit' => round((float) $sale->paid_amount, 2),
        ]);

        return $this->statement($entries, $from, $to, [
            $this->col('date', 'التاريخ', 'date'),
            $this->col('number', 'الفاتورة', 'code'),
            $this->col('details', 'البيان'),
            $this->col('kg', 'الوزن', 'kg'),
            $this->col('debit', 'قيمة الفاتورة', 'money'),
            $this->col('credit', 'المحصّل', 'money'),
            $this->col('balance', 'الرصيد', 'money', sum: false, strong: true),
        ], 'فاتورة') + ['party' => $customer, 'last' => $sales->last()?->sold_at];
    }

    /**
     * كشف حساب مورد: سندات المالك عليه بالتاريخ، والمسدَّد منها، ورصيد جارٍ.
     *
     * @return array<string, mixed>
     */
    public function vendorStatement(User $owner, Vendor $vendor, ?string $from, ?string $to): array
    {
        $expenses = Expense::forOwner($owner)->where('vendor_id', $vendor->id)
            ->with(['category:id,name', 'boat:id,name'])
            ->orderBy('date')->orderBy('id')->get();

        $entries = $expenses->map(fn (Expense $e) => [
            'date' => $e->date->format('Y-m-d'),
            'number' => $e->expense_number,
            'details' => trim(($e->category?->name ?? '').($e->boat ? ' — '.$e->boat->name : '').($e->description ? ' — '.$e->description : ''), ' —') ?: '—',
            'debit' => round($e->total, 2),
            'credit' => round($e->paid_amount, 2),
        ]);

        return $this->statement($entries, $from, $to, [
            $this->col('date', 'التاريخ', 'date'),
            $this->col('number', 'السند', 'code'),
            $this->col('details', 'البيان'),
            $this->col('debit', 'قيمة السند', 'money'),
            $this->col('credit', 'المسدَّد', 'money'),
            $this->col('balance', 'الرصيد', 'money', sum: false, strong: true),
        ], 'سند') + ['party' => $vendor, 'last' => $expenses->last()?->date];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries  مرتّبة بالتاريخ
     * @return array<string, mixed>
     */
    private function statement(Collection $entries, ?string $from, ?string $to, array $columns, string $noun): array
    {
        $before = $from ? $entries->filter(fn ($e) => $e['date'] < $from) : collect();
        $opening = round($before->sum('debit') - $before->sum('credit'), 2);

        $balance = $opening;
        $rows = $entries
            ->filter(fn ($e) => (! $from || $e['date'] >= $from) && (! $to || $e['date'] <= $to))
            ->map(function ($e) use (&$balance) {
                $balance = round($balance + $e['debit'] - $e['credit'], 2);

                return $e + ['balance' => $balance];
            })->values()->all();

        $table = $this->table($columns, $rows);

        if ($table['totals'] !== null) {
            $table['totals']['balance'] = $balance;
        }

        return [
            'table' => $table,
            'opening' => $from ? $opening : null,
            'closing' => $balance,
            'count' => count($rows),
            'all_time' => round($entries->sum('debit') - $entries->sum('credit'), 2),
            'notes' => [
                ($noun === 'فاتورة' ? 'كل فاتورة تُقيَّد بقيمتها ويقابلها ما حُصِّل منها' : 'كل سند يُقيَّد بقيمته ويقابله ما سُدِّد منه').'، والرصيد الجاري = الرصيد الافتتاحي + القيم − '.($noun === 'فاتورة' ? 'المحصّل.' : 'المسدَّد.'),
            ],
        ];
    }

    // ─────────────────────────── مصادر مشتركة ───────────────────────────

    /**
     * صافي الإيراد مقسومًا: بيع مباشر، وبيع الدلال بإجماليه واقتطاعه وصافيه.
     *
     * @return array{direct_net: float, dalal_gross: float, dalal_cut: float, dalal_net: float, net: float}
     */
    public function revenueSplit(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?int $boatId = null): array
    {
        $tripIds = $boatId ? Trip::where('boat_id', $boatId)->pluck('id') : null;
        $sales = $this->salesByTrip($owner, $from, $to, $tripIds);

        $direct = round($sales->sum('direct_net'), 2);
        $dalalNet = round($sales->sum('dalal_net'), 2);

        return [
            'direct_net' => $direct,
            'dalal_gross' => round($sales->sum('dalal_gross'), 2),
            'dalal_cut' => round($sales->sum('dalal_cut'), 2),
            'dalal_net' => $dalalNet,
            'net' => round($direct + $dalalNet, 2),
        ];
    }

    /**
     * المبيعات لكل رحلة: المباشرة (من الفاتورة) وسطور الدلال (من سطور المالك).
     *
     * @return Collection<int, array<string, float>>
     */
    private function salesByTrip(User $owner, ?CarbonImmutable $from, ?CarbonImmutable $to, ?Collection $tripIds = null): Collection
    {
        $range = fn ($q) => $q
            ->when($from, fn ($q) => $q->where('sales.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.sold_at', '<=', $to));

        $direct = Sale::where('sales.seller_id', $owner->id)->whereNotNull('sales.trip_id')
            ->when($tripIds, fn ($q) => $q->whereIn('sales.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sales.trip_id AS trip_id, SUM(sales.total) AS gross, SUM(sales.owner_net) AS net')
            ->groupBy('sales.trip_id')->get()->keyBy('trip_id');

        $directKg = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.seller_id', $owner->id)->whereNotNull('sales.trip_id')
            ->when($tripIds, fn ($q) => $q->whereIn('sales.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sales.trip_id AS trip_id, SUM(sale_items.weight_kg) AS kg')
            ->groupBy('sales.trip_id')->pluck('kg', 'trip_id');

        $dalal = SaleItem::soldByDalalFor($owner)->whereNotNull('sale_items.trip_id')
            ->when($tripIds, fn ($q) => $q->whereIn('sale_items.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sale_items.trip_id AS trip_id, SUM(sale_items.total) AS gross, SUM(sale_items.commission_amount + sale_items.wage_amount) AS cut, SUM(sale_items.owner_net) AS net, SUM(sale_items.weight_kg) AS kg')
            ->groupBy('sale_items.trip_id')->get()->keyBy('trip_id');

        return $direct->keys()->merge($dalal->keys())->unique()->mapWithKeys(fn ($tripId) => [(int) $tripId => [
            'direct_gross' => round((float) ($direct[$tripId]->gross ?? 0), 2),
            'direct_net' => round((float) ($direct[$tripId]->net ?? 0), 2),
            'direct_kg' => round((float) ($directKg[$tripId] ?? 0), 2),
            'dalal_gross' => round((float) ($dalal[$tripId]->gross ?? 0), 2),
            'dalal_cut' => round((float) ($dalal[$tripId]->cut ?? 0), 2),
            'dalal_net' => round((float) ($dalal[$tripId]->net ?? 0), 2),
            'dalal_kg' => round((float) ($dalal[$tripId]->kg ?? 0), 2),
        ]]);
    }

    /**
     * المباع لكل صنف: السطور المباشرة بعد توزيع خصم فاتورتها، وسطور الدلال
     * بإجماليها واقتطاعها وصافيها.
     *
     * @return array<int, array<string, mixed>>
     */
    private function speciesSold(User $owner, ?CarbonImmutable $from, ?CarbonImmutable $to, ?Collection $tripIds = null): array
    {
        $range = fn ($q) => $q
            ->when($from, fn ($q) => $q->where('sales.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('sales.sold_at', '<=', $to));

        $direct = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.seller_id', $owner->id)
            ->when($tripIds, fn ($q) => $q->whereIn('sales.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sale_items.species_id AS species_id, SUM(sale_items.weight_kg) AS kg, SUM(CASE WHEN sales.subtotal > 0 THEN sale_items.total * sales.total / sales.subtotal ELSE sale_items.total END) AS gross')
            ->groupBy('sale_items.species_id')->get()->keyBy('species_id');

        $dalal = SaleItem::soldByDalalFor($owner)
            ->when($tripIds, fn ($q) => $q->whereIn('sale_items.trip_id', $tripIds))
            ->tap($range)
            ->selectRaw('sale_items.species_id AS species_id, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS gross, SUM(sale_items.commission_amount + sale_items.wage_amount) AS cut, SUM(sale_items.owner_net) AS net')
            ->groupBy('sale_items.species_id')->get()->keyBy('species_id');

        $ids = $direct->keys()->merge($dalal->keys())->unique();
        $names = Species::whereIn('id', $ids)->pluck('name_ar', 'id');

        return $ids->map(function ($id) use ($direct, $dalal, $names) {
            $directKg = round((float) ($direct[$id]->kg ?? 0), 2);
            $directGross = round((float) ($direct[$id]->gross ?? 0), 2);
            $dalalKg = round((float) ($dalal[$id]->kg ?? 0), 2);
            $gross = round($directGross + (float) ($dalal[$id]->gross ?? 0), 2);
            $kg = round($directKg + $dalalKg, 2);

            return [
                'species_id' => (int) $id,
                'species' => $names[$id] ?? '—',
                'direct_kg' => $directKg,
                'dalal_kg' => $dalalKg,
                'kg' => $kg,
                'gross' => $gross,
                'cut' => round((float) ($dalal[$id]->cut ?? 0), 2),
                'net' => round($directGross + (float) ($dalal[$id]->net ?? 0), 2),
                'avg_price' => $this->ratio($gross, $kg),
            ];
        })->values()->all();
    }

    /**
     * @return Collection<int, float>
     */
    private function caughtByTrip(Collection $tripIds): Collection
    {
        return CatchRecord::whereIn('trip_id', $tripIds)
            ->selectRaw('trip_id, SUM(COALESCE(counted_kg, quantity_kg)) AS kg')
            ->groupBy('trip_id')->pluck('kg', 'trip_id')
            ->map(fn ($kg) => round((float) $kg, 2));
    }

    /**
     * المصروفات مجمّعة: مجموعة ← فئات بأرقامها.
     *
     * @return array{groups: array<int, array{name: string, total: float, categories: array<int, array<string, mixed>>}>, total: float}
     */
    private function expenseCategories(User $owner, CarbonImmutable $from, CarbonImmutable $to, ?string $boat): array
    {
        $rows = Expense::forOwner($owner)
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->leftJoin('expense_groups', 'expense_groups.id', '=', 'expense_categories.expense_group_id')
            ->whereDate('expenses.date', '>=', $from->toDateString())
            ->whereDate('expenses.date', '<=', $to->toDateString())
            ->when($boat === 'general', fn ($q) => $q->whereNull('expenses.boat_id'))
            ->when($boat !== null && $boat !== 'general', fn ($q) => $q->where('expenses.boat_id', (int) $boat))
            ->selectRaw('expense_groups.name AS group_name, expense_groups.id AS group_id, expense_categories.name AS category, COUNT(*) AS n,
                SUM(expenses.subtotal) AS subtotal, SUM(expenses.discount) AS discount, SUM(expenses.vat_amount) AS vat,
                SUM(expenses.total) AS total, SUM(expenses.paid_amount) AS paid')
            ->groupBy('expense_groups.id', 'expense_groups.name', 'expense_categories.id', 'expense_categories.name')
            ->get();

        $groups = $rows->groupBy(fn ($r) => $r->group_id ?? 0)->map(function (Collection $cats) {
            $categories = $cats->map(fn ($c) => [
                'name' => $c->category ?? '—',
                'count' => (int) $c->n,
                'subtotal' => round((float) $c->subtotal, 2),
                'discount' => round((float) $c->discount, 2),
                'vat' => round((float) $c->vat, 2),
                'total' => round((float) $c->total, 2),
                'paid' => round((float) $c->paid, 2),
                'remaining' => round((float) $c->total - (float) $c->paid, 2),
            ])->sortByDesc('total')->values()->all();

            return [
                'name' => $cats->first()->group_name ?? '—',
                'total' => round(array_sum(array_column($categories, 'total')), 2),
                'categories' => $categories,
            ];
        })->sortByDesc('total')->values()->all();

        return ['groups' => $groups, 'total' => round(array_sum(array_column($groups, 'total')), 2)];
    }

    /**
     * @return array<string, float>
     */
    private function emptySales(): array
    {
        return array_fill_keys(['direct_gross', 'direct_net', 'direct_kg', 'dalal_gross', 'dalal_cut', 'dalal_net', 'dalal_kg'], 0.0);
    }

    private function percent(float $part, float $whole): ?float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : null;
    }

    private function ratio(float $a, float $b): ?float
    {
        return $b > 0 ? round($a / $b, 2) : null;
    }

    /**
     * عمود جدول: `format` = text | code | date | money | price | kg | int | pct.
     * المجموع افتراضيًا للمبالغ والأوزان والأعداد.
     *
     * @return array{key: string, label: string, format: string, sum: bool, strong: bool}
     */
    private function col(string $key, string $label, string $format = 'text', ?bool $sum = null, bool $strong = false): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'format' => $format,
            'sum' => $sum ?? in_array($format, ['money', 'kg', 'int'], true),
            'strong' => $strong,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $overrides  مجاميع لا تُجمع (نسب، أرصدة)
     * @return array{title: ?string, columns: array, rows: array, totals: ?array}
     */
    private function table(array $columns, array $rows, array $overrides = [], ?string $title = null): array
    {
        $totals = null;

        if ($rows !== []) {
            $totals = [];
            foreach ($columns as $column) {
                if ($column['sum']) {
                    $totals[$column['key']] = round(array_sum(array_map(fn ($row) => (float) ($row[$column['key']] ?? 0), $rows)), 2);
                }
            }
            $totals = $overrides + $totals;
        }

        return ['title' => $title, 'columns' => $columns, 'rows' => $rows, 'totals' => $totals];
    }
}
