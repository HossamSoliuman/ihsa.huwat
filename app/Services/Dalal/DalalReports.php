<?php

namespace App\Services\Dalal;

use App\Models\Sale;
use App\Models\Species;
use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\Trip;
use App\Models\User;
use App\Services\Stock\StockLedger;
use Illuminate\Support\Carbon;

/**
 * تقارير الدلال الأربعة كما في لوحته القديمة: المبيعات، والمخزون المستلم من
 * الصيادين، ومدفوعات الصيادين المستحقة، والتقرير المالي. كل تقرير بنية واحدة
 * (أعمدة، سطور، مجاميع) تطبعها صفحة واحدة في الويب ويعيدها التطبيق كما هي.
 */
class DalalReports
{
    public const TYPES = [
        'sales' => ['title' => 'تقرير المبيعات', 'description' => 'تقرير شامل لجميع عمليات البيع', 'icon' => 'coins'],
        'stock' => ['title' => 'تقرير المخزون', 'description' => 'تقرير المصيد المستلم من الصيادين', 'icon' => 'archive'],
        'payouts' => ['title' => 'تقرير مدفوعات الصيادين', 'description' => 'تقرير المدفوعات المستحقة للصيادين', 'icon' => 'users'],
        'financial' => ['title' => 'التقرير المالي', 'description' => 'ملخص الإيرادات والأرباح', 'icon' => 'calculator'],
    ];

    public function __construct(private readonly DalalAccounts $accounts) {}

    /**
     * @param  array{from?:string|null, to?:string|null, status?:string|null, payment_status_id?:int|string|null, species_id?:int|string|null, owner_id?:int|string|null}  $filters
     * @return array{type:string, title:string, description:string, filters:array, columns:array<int, array{key:string, label:string, format:string}>, rows:array<int, array<string, mixed>>, totals:array<string, float|int>}
     */
    public function build(User $dalal, string $type, array $filters = []): array
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');
        [$columns, $rows] = match ($type) {
            'sales' => $this->sales($dalal, $filters),
            'stock' => $this->stock($dalal, $filters),
            'payouts' => $this->payouts($dalal, $filters),
            'financial' => $this->financial($dalal, $filters),
        };

        $totals = [];
        foreach ($columns as $column) {
            if (in_array($column['format'], ['money', 'kg', 'int'], true)) {
                $totals[$column['key']] = round(array_sum(array_column($rows, $column['key'])), 2);
            }
        }

        return self::TYPES[$type] + [
            'type' => $type,
            'filters' => $filters,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function sales(User $dalal, array $f): array
    {
        $sales = Sale::forSeller($dalal)->with(['customer:id,name', 'paymentStatus:id,name'])->withSum('items', 'weight_kg')
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('sold_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('sold_at', '<=', $v))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['payment_status_id'] ?? null, fn ($q, $v) => $q->where('payment_status_id', $v))
            ->when($f['species_id'] ?? null, fn ($q, $v) => $q->whereHas('items', fn ($i) => $i->where('species_id', $v)))
            ->when($f['owner_id'] ?? null, fn ($q, $v) => $q->whereHas('items', fn ($i) => $i->where('owner_id', $v)))
            ->orderBy('sold_at')->get();

        return [[
            $this->col('invoice_number', 'رقم الفاتورة'),
            $this->col('sold_at', 'التاريخ', 'date'),
            $this->col('customer', 'العميل'),
            $this->col('weight_kg', 'الوزن', 'kg'),
            $this->col('total', 'الإجمالي', 'money'),
            $this->col('commission', 'العمولة', 'money'),
            $this->col('wages', 'الأجور', 'money'),
            $this->col('owner_net', 'صافي المالك', 'money'),
            $this->col('paid', 'المدفوع', 'money'),
            $this->col('status', 'الحالة'),
        ], $sales->map(fn (Sale $sale) => [
            'invoice_number' => $sale->invoice_number,
            'sold_at' => $sale->sold_at?->format('Y-m-d H:i'),
            'customer' => $sale->customer?->name ?? '—',
            'weight_kg' => round((float) $sale->items_sum_weight_kg, 2),
            'total' => (float) $sale->total,
            'commission' => (float) $sale->commission_amount,
            'wages' => (float) $sale->wage_amount,
            'owner_net' => (float) $sale->owner_net,
            'paid' => (float) $sale->paid_amount,
            'status' => $sale->status.($sale->paymentStatus ? ' / '.$sale->paymentStatus->name : ''),
        ])->all()];
    }

    /**
     * كل دفعة مستلمة (رحلة × صنف): ما استُلم، وما بيع منها، والمتبقي.
     */
    private function stock(User $dalal, array $f): array
    {
        $inType = StockMovementType::named(StockLedger::CONSIGN_IN)->id;

        $rows = StockMovement::forHolder($dalal)
            ->when($f['species_id'] ?? null, fn ($q, $v) => $q->where('species_id', $v))
            ->selectRaw('trip_id, species_id, MIN(created_at) AS first_in,
                SUM(CASE WHEN stock_movement_type_id = ? THEN weight_kg ELSE 0 END) AS received,
                SUM(CASE WHEN weight_kg < 0 THEN -weight_kg ELSE 0 END) AS sold,
                SUM(weight_kg) AS available', [$inType])
            ->groupBy('trip_id', 'species_id')
            ->orderBy('first_in')
            ->get();

        $trips = Trip::with('owner:id,name')->whereIn('id', $rows->pluck('trip_id')->filter())->get(['id', 'trip_number', 'owner_id'])->keyBy('id');
        $species = Species::whereIn('id', $rows->pluck('species_id'))->pluck('name_ar', 'id');

        $from = isset($f['from']) ? Carbon::parse($f['from'])->startOfDay() : null;
        $to = isset($f['to']) ? Carbon::parse($f['to'])->endOfDay() : null;

        $rows = $rows
            ->filter(fn ($row) => (! $from || Carbon::parse($row->first_in)->gte($from)) && (! $to || Carbon::parse($row->first_in)->lte($to)))
            ->filter(fn ($row) => ! isset($f['owner_id']) || (int) ($trips[$row->trip_id]->owner_id ?? 0) === (int) $f['owner_id']);

        return [[
            $this->col('received_at', 'تاريخ الاستلام', 'date'),
            $this->col('owner', 'المالك'),
            $this->col('trip_number', 'الرحلة'),
            $this->col('species', 'الصنف'),
            $this->col('received_kg', 'المستلم', 'kg'),
            $this->col('sold_kg', 'المباع', 'kg'),
            $this->col('available_kg', 'المتبقي', 'kg'),
        ], $rows->map(fn ($row) => [
            'received_at' => Carbon::parse($row->first_in)->format('Y-m-d H:i'),
            'owner' => $trips[$row->trip_id]->owner->name ?? '—',
            'trip_number' => $trips[$row->trip_id]->trip_number ?? '—',
            'species' => $species[$row->species_id] ?? '—',
            'received_kg' => round((float) $row->received, 2),
            'sold_kg' => round((float) $row->sold, 2),
            'available_kg' => round((float) $row->available, 2),
        ])->values()->all()];
    }

    private function payouts(User $dalal, array $f): array
    {
        $owners = $this->accounts->owners($dalal)
            ->when($f['owner_id'] ?? null, fn ($rows, $v) => $rows->where('id', (int) $v));

        return [[
            $this->col('name', 'المالك'),
            $this->col('phone', 'الجوال'),
            $this->col('received_kg', 'المستلم', 'kg'),
            $this->col('sold_kg', 'المباع', 'kg'),
            $this->col('sales_total', 'قيمة المبيعات', 'money'),
            $this->col('deductions', 'العمولة والأجور', 'money'),
            $this->col('owner_net', 'صافي المالك', 'money'),
            $this->col('paid', 'المدفوع', 'money'),
            $this->col('due', 'المستحق', 'money'),
        ], $owners->map(fn ($row) => collect($row)->only(['name', 'phone', 'received_kg', 'sold_kg', 'sales_total', 'deductions', 'owner_net', 'paid', 'due'])->all())->values()->all()];
    }

    /**
     * الإيرادات والأرباح شهرًا شهرًا في الفترة (السنة الحالية إن لم تُحدَّد).
     */
    private function financial(User $dalal, array $f): array
    {
        $from = isset($f['from']) ? Carbon::parse($f['from'])->startOfMonth() : now()->startOfYear();
        $to = isset($f['to']) ? Carbon::parse($f['to'])->endOfDay() : now()->endOfDay();

        $sales = Sale::forSeller($dalal)->whereBetween('sold_at', [$from, $to])
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->get(['sold_at', 'total', 'commission_amount', 'wage_amount', 'owner_net', 'paid_amount'])
            ->groupBy(fn (Sale $sale) => $sale->sold_at->format('Y-m'));

        $rows = [];
        for ($month = $from->copy(); $month->lte($to); $month->addMonth()) {
            $group = $sales[$month->format('Y-m')] ?? collect();
            $commission = round((float) $group->sum('commission_amount'), 2);
            $wages = round((float) $group->sum('wage_amount'), 2);
            $rows[] = [
                'month' => $month->translatedFormat('F Y'),
                'sales_count' => $group->count(),
                'revenue' => round((float) $group->sum('total'), 2),
                'commission' => $commission,
                'wages' => $wages,
                'profit' => round($commission + $wages, 2),
                'owner_net' => round((float) $group->sum('owner_net'), 2),
                'collected' => round((float) $group->sum('paid_amount'), 2),
            ];
        }

        return [[
            $this->col('month', 'الشهر'),
            $this->col('sales_count', 'عدد المبيعات', 'int'),
            $this->col('revenue', 'الإيرادات', 'money'),
            $this->col('commission', 'العمولة', 'money'),
            $this->col('wages', 'الأجور', 'money'),
            $this->col('profit', 'صافي الربح', 'money'),
            $this->col('owner_net', 'مستحق الملاك', 'money'),
            $this->col('collected', 'المحصّل', 'money'),
        ], $rows];
    }

    private function col(string $key, string $label, string $format = 'text'): array
    {
        return ['key' => $key, 'label' => $label, 'format' => $format];
    }
}
