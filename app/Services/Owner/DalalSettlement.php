<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Consignment;
use App\Models\DalalInvoiceReview;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\Role;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Trip;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * تسوية المالك مع دلاليه (O5): فواتير الدلال من جهته، وحساب كل دلال،
 * وكشفه، وما يستلمه منه.
 *
 * "فاتورة الدلال" عند المالك = سطور مصيده في بيعٍ واحد من مخزون الدلال
 * (الفاتورة قد تجمع ملاكًا آخرين لا يرى سطورهم). صافيها = Σ owner_net.
 * المستحق عند الدلال = Σ صافي الفواتير − Σ الدفعات (سجّلها الدلال أو المالك).
 * الدفعات لا تُربط بفاتورة: تُوزَّع على فواتير الدلال **الأقدم أوّلًا** فتُشتق
 * حالة سداد كل فاتورة (مسدَّدة / جزئيًا / غير مسدَّدة) — كما حالة الدفع في
 * hispa بلا سطر دفع لكل فاتورة.
 */
class DalalSettlement
{
    public const PAYMENT_LABELS = ['paid' => 'مسدَّدة', 'partial' => 'مسدَّدة جزئيًا', 'unpaid' => 'غير مسدَّدة'];

    public const PAYMENT_BADGES = ['paid' => 'badge-ok', 'partial' => 'badge-warn', 'unpaid' => 'badge-danger'];

    public function __construct(private readonly Notifier $notifier) {}

    /**
     * فواتير الدلالين للمالك بأرقام سطوره وحالتي المراجعة والسداد، الأحدث أوّلًا.
     *
     * @param  array{dalal_id?:int|string|null, status?:string|null, payment?:string|null, from?:string|null, to?:string|null, search?:string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function invoices(User $owner, array $filters = []): Collection
    {
        $reviews = DalalInvoiceReview::forOwner($owner)
            ->when($filters['dalal_id'] ?? null, fn ($q, $id) => $q->where('dalal_id', $id))
            ->with(['sale:id,invoice_number,sold_at,customer_id,seller_id', 'sale.customer:id,name', 'dalal:id,name,phone'])
            ->get();

        if ($reviews->isEmpty()) {
            return collect();
        }

        $saleIds = $reviews->pluck('sale_id');
        $items = SaleItem::where('owner_id', $owner->id)->whereIn('sale_id', $saleIds)
            ->with(['trip:id,trip_number,boat_id', 'trip.boat:id,name'])
            ->get()->groupBy('sale_id');
        $paid = DalalPayout::forOwner($owner)->whereIn('dalal_id', $reviews->pluck('dalal_id')->unique())
            ->selectRaw('dalal_id, SUM(amount) AS amount')->groupBy('dalal_id')->pluck('amount', 'dalal_id');

        $rows = $reviews->map(function (DalalInvoiceReview $review) use ($items) {
            $lines = $items->get($review->sale_id, collect());
            $commission = round((float) $lines->sum('commission_amount'), 2);
            $wage = round((float) $lines->sum('wage_amount'), 2);

            return [
                'sale_id' => $review->sale_id,
                'invoice_number' => $review->sale?->invoice_number,
                'sold_at' => $review->sale?->sold_at,
                'dalal_id' => $review->dalal_id,
                'dalal' => $review->dalal?->name,
                'customer' => $review->sale?->customer?->name,
                'trips' => $lines->pluck('trip.trip_number')->filter()->unique()->values()->all(),
                'boats' => $lines->pluck('trip.boat.name')->filter()->unique()->values()->all(),
                'species_count' => $lines->pluck('species_id')->unique()->count(),
                'weight_kg' => round((float) $lines->sum('weight_kg'), 2),
                'total' => round((float) $lines->sum('total'), 2),
                'commission' => $commission,
                'wage' => $wage,
                'deductions' => round($commission + $wage, 2),
                'owner_net' => round((float) $lines->sum('owner_net'), 2),
                'review' => $review,
            ];
        });

        $rows = $this->allocate($rows, $paid);

        return $rows
            ->when($filters['status'] ?? null, fn ($c, $status) => $c->filter(fn ($row) => $row['review']->status === $status))
            ->when($filters['payment'] ?? null, fn ($c, $payment) => $c->where('payment', $payment))
            ->when($filters['from'] ?? null, fn ($c, $from) => $c->filter(fn ($row) => $row['sold_at']?->toDateString() >= $from))
            ->when($filters['to'] ?? null, fn ($c, $to) => $c->filter(fn ($row) => $row['sold_at']?->toDateString() <= $to))
            ->when($filters['search'] ?? null, fn ($c, $search) => $c->filter(fn ($row) => str_contains((string) $row['invoice_number'], $search)))
            ->sortByDesc(fn ($row) => [$row['sold_at']?->timestamp ?? 0, $row['sale_id']])
            ->values();
    }

    /**
     * فاتورة واحدة: سطور مصيد المالك فيها بصنفها ورحلتها وقاربها، وأرقامها
     * وحالة سدادها بين فواتير دلالها.
     *
     * @return array{invoice: array<string, mixed>, lines: Collection<int, SaleItem>}
     */
    public function invoice(User $owner, DalalInvoiceReview $review): array
    {
        return [
            'invoice' => $this->invoices($owner, ['dalal_id' => $review->dalal_id])->firstWhere('sale_id', $review->sale_id),
            'lines' => SaleItem::where('owner_id', $owner->id)->where('sale_id', $review->sale_id)
                ->with(['species:id,name_ar,name_sci', 'trip:id,trip_number,boat_id', 'trip.boat:id,name'])
                ->orderBy('id')->get(),
        ];
    }

    /**
     * بطاقات الفواتير والحسابات ورئيسة المالك.
     *
     * @return array{invoices: int, owner_net: float, paid: float, balance: float, pending: int, rejected: int}
     */
    public function summary(User $owner): array
    {
        $net = (float) SaleItem::soldByDalalFor($owner)->sum('sale_items.owner_net');
        $paid = (float) DalalPayout::forOwner($owner)->sum('amount');
        $counts = DalalInvoiceReview::forOwner($owner)->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status');

        return [
            'invoices' => (int) $counts->sum(),
            'owner_net' => round($net, 2),
            'paid' => round($paid, 2),
            'balance' => round($net - $paid, 2),
            'pending' => (int) ($counts[DalalInvoiceReview::PENDING] ?? 0),
            'rejected' => (int) ($counts[DalalInvoiceReview::REJECTED] ?? 0),
        ];
    }

    /**
     * حساب المالك عند كل دلال تعامل معه: ما أرسله، وما بقي في مخزونه، وما
     * بيع وصافيه، والمستلم، والمستحق، وحال المراجعة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function accounts(User $owner, ?string $search = null): Collection
    {
        $ids = $this->dalalIds($owner);

        if ($ids->isEmpty()) {
            return collect();
        }

        $dalals = User::whereIn('id', $ids)->with('dalalProfile.port')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->orderBy('name')->get(['id', 'name', 'phone']);

        $sent = Consignment::forOwner($owner)->selectRaw('dalal_id, SUM(total_kg) AS kg, MAX(sent_at) AS last_at')->groupBy('dalal_id')->get()->keyBy('dalal_id');
        $inStock = $this->heldByDalal($owner)->pluck('kg', 'holder_id');
        $sold = SaleItem::soldByDalalFor($owner)
            ->selectRaw('sales.seller_id AS dalal_id, COUNT(DISTINCT sale_items.sale_id) AS invoices, SUM(sale_items.weight_kg) AS kg, SUM(sale_items.total) AS total, SUM(sale_items.commission_amount + sale_items.wage_amount) AS cut, SUM(sale_items.owner_net) AS net, MAX(sales.sold_at) AS last_at')
            ->groupBy('sales.seller_id')->get()->keyBy('dalal_id');
        $paid = DalalPayout::forOwner($owner)->selectRaw('dalal_id, SUM(amount) AS amount, MAX(paid_at) AS last_at')->groupBy('dalal_id')->get()->keyBy('dalal_id');
        $reviews = DalalInvoiceReview::forOwner($owner)->selectRaw('dalal_id, status, COUNT(*) AS n')->groupBy('dalal_id', 'status')->get()->groupBy('dalal_id');
        $partnerships = DalalPartnership::forOwner($owner)->get()->keyBy('dalal_id');

        return $dalals->map(function (User $dalal) use ($sent, $inStock, $sold, $paid, $reviews, $partnerships) {
            $net = round((float) ($sold[$dalal->id]->net ?? 0), 2);
            $received = round((float) ($paid[$dalal->id]->amount ?? 0), 2);
            $statuses = ($reviews[$dalal->id] ?? collect())->pluck('n', 'status');

            return [
                'id' => $dalal->id,
                'name' => $dalal->name,
                'phone' => $dalal->phone,
                'dakka' => $dalal->dalalProfile?->dakka_name,
                'port' => $dalal->dalalProfile?->port?->name,
                'partnership' => $partnerships[$dalal->id] ?? null,
                'sent_kg' => round((float) ($sent[$dalal->id]->kg ?? 0), 2),
                'in_stock_kg' => round((float) ($inStock[$dalal->id] ?? 0), 2),
                'invoices' => (int) ($sold[$dalal->id]->invoices ?? 0),
                'sold_kg' => round((float) ($sold[$dalal->id]->kg ?? 0), 2),
                'sales_total' => round((float) ($sold[$dalal->id]->total ?? 0), 2),
                'deductions' => round((float) ($sold[$dalal->id]->cut ?? 0), 2),
                'owner_net' => $net,
                'paid' => $received,
                'balance' => round($net - $received, 2),
                'pending' => (int) ($statuses[DalalInvoiceReview::PENDING] ?? 0),
                'rejected' => (int) ($statuses[DalalInvoiceReview::REJECTED] ?? 0),
                'last_sale_at' => isset($sold[$dalal->id]) ? Carbon::parse($sold[$dalal->id]->last_at) : null,
                'last_payout_at' => isset($paid[$dalal->id]) ? Carbon::parse($paid[$dalal->id]->last_at) : null,
                'last_sent_at' => isset($sent[$dalal->id]) ? Carbon::parse($sent[$dalal->id]->last_at) : null,
            ];
        })->values();
    }

    /**
     * كشف حساب المالك عند دلال: الفواتير (صافيها له) والدفعات (المستلم)
     * بالتاريخ ورصيد جارٍ، ورصيد افتتاحي لما قبل بداية الفترة.
     *
     * @return array{entries: Collection<int, array<string, mixed>>, opening: float, totals: array{net: float, paid: float, closing: float}}
     */
    public function statement(User $owner, User $dalal, ?string $from = null, ?string $to = null): array
    {
        $invoices = $this->invoices($owner, ['dalal_id' => $dalal->id])->map(fn ($row) => [
            'date' => $row['sold_at'],
            'kind' => 'invoice',
            'label' => 'فاتورة',
            'number' => $row['invoice_number'],
            'details' => number_format($row['weight_kg'], 1).' كجم — مبيعات '.number_format($row['total'], 2).' − عمولة وأجور '.number_format($row['deductions'], 2),
            'reference' => $row['review']->status_label,
            'sale_id' => $row['sale_id'],
            'net' => $row['owner_net'],
            'paid' => 0.0,
        ]);
        $payouts = DalalPayout::forOwner($owner)->forDalal($dalal)->with('paymentMethod')->get()->map(fn (DalalPayout $payout) => [
            'date' => $payout->paid_at,
            'kind' => 'payout',
            'label' => $payout->recordedByOwner() ? 'استلام سجّلتَه' : 'دفعة سجّلها الدلال',
            'number' => null,
            'details' => trim(($payout->paymentMethod?->name ?? '').($payout->notes ? ' — '.$payout->notes : ''), ' —'),
            'reference' => $payout->reference,
            'payout' => $payout,
            'net' => 0.0,
            'paid' => round((float) $payout->amount, 2),
        ]);

        $all = $invoices->concat($payouts)
            ->sortBy(fn ($entry) => [$entry['date']?->timestamp ?? 0, $entry['kind'] === 'invoice' ? 0 : 1])
            ->values();

        $before = $from ? $all->filter(fn ($e) => $e['date']?->toDateString() < $from) : collect();
        $opening = round($before->sum('net') - $before->sum('paid'), 2);

        $balance = $opening;
        $entries = $all
            ->filter(fn ($e) => (! $from || $e['date']?->toDateString() >= $from) && (! $to || $e['date']?->toDateString() <= $to))
            ->map(function ($entry) use (&$balance) {
                $balance = round($balance + $entry['net'] - $entry['paid'], 2);

                return $entry + ['balance' => $balance];
            })->values();

        return [
            'entries' => $entries,
            'opening' => $opening,
            'totals' => [
                'net' => round($entries->sum('net'), 2),
                'paid' => round($entries->sum('paid'), 2),
                'closing' => $balance,
            ],
        ];
    }

    /**
     * المستحق للمالك عند دلال — الرقم نفسه الذي يراه الدلال (DalalAccounts::dueTo).
     */
    public function balanceWith(User $owner, User $dalal): float
    {
        $net = (float) SaleItem::soldByDalalFor($owner)->where('sales.seller_id', $dalal->id)->sum('sale_items.owner_net');

        return round($net - (float) DalalPayout::forOwner($owner)->forDalal($dalal)->sum('amount'), 2);
    }

    /**
     * المالك يسجّل دفعة استلمها من الدلال (نقدًا أو حوالة لم يسجّلها الدلال)
     * — لا تتجاوز المستحق، ويُبلَّغ الدلال فيراها في حسابه.
     *
     * @param  array{amount:float|string, payment_method_id?:int|null, paid_at?:string|null, reference?:string|null, notes?:string|null}  $data
     */
    public function recordReceipt(User $owner, User $dalal, array $data): DalalPayout
    {
        $amount = round((float) $data['amount'], 2);
        $balance = $this->balanceWith($owner, $dalal);

        if ($amount <= 0 || $amount > $balance) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر ولا يتجاوز المستحق لك عند الدلال ('.number_format(max($balance, 0), 2).' ر.س).']);
        }

        $payout = DalalPayout::create([
            'dalal_id' => $dalal->id,
            'owner_id' => $owner->id,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'amount' => $amount,
            'paid_at' => $data['paid_at'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'user_id' => $owner->id,
        ]);

        $payout->setRelation('dalal', $dalal)->setRelation('owner', $owner);
        $this->notifier->receiptRecorded($payout);
        $this->log('استلام من دلال', $owner, (string) $payout->id, "استلام {$amount} ر.س من {$dalal->name}");

        return $payout;
    }

    /**
     * يحذف استلامًا سجّله المالك نفسه (خطأ إدخال). ما سجّله الدلال يبقى —
     * يُصحَّح من جهته.
     */
    public function deleteReceipt(User $owner, DalalPayout $payout): void
    {
        if (! $payout->recordedByOwner()) {
            throw ValidationException::withMessages(['payout' => 'هذه الدفعة سجّلها الدلال — لا تُحذف من حسابك.']);
        }

        $payout->delete();
        $this->log('حذف استلام من دلال', $owner, (string) $payout->id, 'حذف استلام '.number_format((float) $payout->amount, 2).' ر.س');
    }

    /**
     * الدلال الذي تعامل معه المالك (إرسال، بيع، دفعة، أو اتفاق) — وإلا 404.
     */
    public function linkedDalal(User $owner, int|string $id): User
    {
        abort_unless($this->dalalIds($owner)->contains((int) $id), 404);

        return User::whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))->findOrFail($id);
    }

    /**
     * @return Collection<int, int>
     */
    public function dalalIds(User $owner): Collection
    {
        return Consignment::forOwner($owner)->distinct()->pluck('dalal_id')
            ->merge(DalalInvoiceReview::forOwner($owner)->distinct()->pluck('dalal_id'))
            ->merge(DalalPayout::forOwner($owner)->distinct()->pluck('dalal_id'))
            ->merge(DalalPartnership::forOwner($owner)->accepted()->pluck('dalal_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()->values();
    }

    /**
     * ما بقي في مخزون كل دلال من رحلات المالك — مجموع حركات دفتره عليها.
     *
     * @return Collection<int, object{holder_id:int, kg:float}>
     */
    private function heldByDalal(User $owner): Collection
    {
        return StockMovement::whereIn('trip_id', Trip::forOwner($owner)->select('id'))
            ->where('holder_id', '!=', $owner->id)
            ->selectRaw('holder_id, SUM(weight_kg) AS kg')
            ->groupBy('holder_id')
            ->get();
    }

    /**
     * يوزّع دفعات كل دلال على فواتيره الأقدم أوّلًا فيُشتق المسدَّد من كل
     * فاتورة وحالته.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int|string, mixed>  $paid
     * @return Collection<int, array<string, mixed>>
     */
    private function allocate(Collection $rows, Collection $paid): Collection
    {
        $left = $paid->map(fn ($amount) => round((float) $amount, 2))->all();

        return $rows
            ->sortBy(fn ($row) => [$row['sold_at']?->timestamp ?? 0, $row['sale_id']])
            ->map(function ($row) use (&$left) {
                $available = $left[$row['dalal_id']] ?? 0.0;
                $settled = round(min(max($row['owner_net'], 0), $available), 2);
                $left[$row['dalal_id']] = round($available - $settled, 2);
                $outstanding = round($row['owner_net'] - $settled, 2);
                $payment = match (true) {
                    $outstanding <= 0 => 'paid',
                    $settled > 0 => 'partial',
                    default => 'unpaid',
                };

                return $row + [
                    'settled' => $settled,
                    'outstanding' => $outstanding,
                    'payment' => $payment,
                    'payment_label' => self::PAYMENT_LABELS[$payment],
                    'payment_badge' => self::PAYMENT_BADGES[$payment],
                ];
            })
            ->values();
    }

    private function log(string $action, User $by, string $label, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => 'DalalPayout',
            'record_label' => $label,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
