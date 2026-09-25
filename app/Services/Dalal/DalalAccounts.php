<?php

namespace App\Services\Dalal;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\Consignment;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * الصيّادون المرتبطون بالدلال وحساب كلٍّ منهم عنده: ما استلمه منه، وما
 * باعه من مصيده وصافيه بعد العمولة والأجور، وما دفعه له، والمستحق.
 * المرتبط = من أرسل إليه مصيدًا أو قُبل طلب تعامله.
 */
class DalalAccounts
{
    public function __construct(
        private readonly DalalStock $stock,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function owners(User $dalal, ?string $search = null, ?int $regionId = null): Collection
    {
        $ownerIds = Consignment::forDalal($dalal)->distinct()->pluck('owner_id')
            ->merge(DalalPartnership::forDalal($dalal)->accepted()->pluck('owner_id'))
            ->unique()->values();

        $owners = User::whereIn('id', $ownerIds)
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $received = Consignment::forDalal($dalal)->selectRaw('owner_id, SUM(total_kg) AS kg, COUNT(*) AS n')->groupBy('owner_id')->get()->keyBy('owner_id');
        $sold = SaleItem::whereHas('sale', fn ($q) => $q->where('seller_id', $dalal->id))
            ->selectRaw('owner_id, SUM(weight_kg) AS kg, SUM(total) AS total, SUM(commission_amount + wage_amount) AS cut, SUM(owner_net) AS net')
            ->groupBy('owner_id')->get()->keyBy('owner_id');
        $paid = DalalPayout::forDalal($dalal)->selectRaw('owner_id, SUM(amount) AS amount')->groupBy('owner_id')->pluck('amount', 'owner_id');
        $partnerships = DalalPartnership::forDalal($dalal)->get()->keyBy('owner_id');
        $inStock = $this->stock->lots($dalal)->groupBy('owner_id')->map(fn ($lots) => round($lots->sum('available_kg'), 2));
        $boats = Boat::whereIn('owner_id', $ownerIds)->with('port.governorate.region')->get()->groupBy('owner_id');

        return $owners->map(function (User $owner) use ($received, $sold, $paid, $partnerships, $inStock, $boats) {
            $port = $boats[$owner->id][0]->port ?? null;
            $net = round((float) ($sold[$owner->id]->net ?? 0), 2);
            $payouts = round((float) ($paid[$owner->id] ?? 0), 2);
            $partnership = $partnerships[$owner->id] ?? null;

            return [
                'id' => $owner->id,
                'name' => $owner->name,
                'phone' => $owner->phone,
                'boats_count' => isset($boats[$owner->id]) ? $boats[$owner->id]->count() : 0,
                'port' => $port?->name,
                'governorate' => $port?->governorate?->name,
                'region' => $port?->governorate?->region?->name,
                'region_id' => $port?->governorate?->region_id,
                'partnership' => $partnership ? [
                    'id' => $partnership->id,
                    'status' => $partnership->status,
                    'status_label' => $partnership->status_label,
                    'commission_pct' => (float) $partnership->commission_pct,
                    'wage_pct' => (float) $partnership->wage_pct,
                ] : null,
                'consignments_count' => (int) ($received[$owner->id]->n ?? 0),
                'received_kg' => round((float) ($received[$owner->id]->kg ?? 0), 2),
                'in_stock_kg' => (float) ($inStock[$owner->id] ?? 0),
                'sold_kg' => round((float) ($sold[$owner->id]->kg ?? 0), 2),
                'sales_total' => round((float) ($sold[$owner->id]->total ?? 0), 2),
                'deductions' => round((float) ($sold[$owner->id]->cut ?? 0), 2),
                'owner_net' => $net,
                'paid' => $payouts,
                'due' => round($net - $payouts, 2),
            ];
        })
            ->when($regionId, fn ($rows) => $rows->where('region_id', $regionId))
            ->values();
    }

    public function dueTo(User $dalal, User $owner): float
    {
        $net = (float) SaleItem::where('owner_id', $owner->id)->whereHas('sale', fn ($q) => $q->where('seller_id', $dalal->id))->sum('owner_net');

        return round($net - (float) DalalPayout::forDalal($dalal)->where('owner_id', $owner->id)->sum('amount'), 2);
    }

    /**
     * الدلال يسجّل دفعة لمالك من صافي مبيعات مصيده — لا تتجاوز المستحق.
     *
     * @param  array{amount:float|string, payment_method_id?:int|null, paid_at?:string|null, notes?:string|null}  $data
     */
    public function recordPayout(User $dalal, User $owner, array $data): DalalPayout
    {
        $amount = round((float) $data['amount'], 2);
        $due = $this->dueTo($dalal, $owner);

        if ($amount <= 0 || $amount > $due) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر ولا يتجاوز المستحق للمالك ('.number_format($due, 2).' ر.س).']);
        }

        $payout = DalalPayout::create([
            'dalal_id' => $dalal->id,
            'owner_id' => $owner->id,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'amount' => $amount,
            'paid_at' => $data['paid_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
            'user_id' => $dalal->id,
        ]);

        $payout->setRelation('dalal', $dalal)->setRelation('owner', $owner);
        $this->notifier->payoutRecorded($payout);

        AuditLog::create([
            'user_email' => $dalal->email ?? $dalal->phone,
            'role' => $dalal->app_role_key ?? 'dalal',
            'action' => 'دفعة لمالك',
            'entity' => 'DalalPayout',
            'record_label' => (string) $payout->id,
            'details' => "دفع {$amount} ر.س إلى {$owner->name}",
            'ip' => request()?->ip(),
        ]);

        return $payout;
    }

    /**
     * هل هذا الحساب مالك مرتبط بالدلال؟ — تقييد صفحة المالك ودفعاته.
     */
    public function linkedOwner(User $dalal, int|string $ownerId): User
    {
        $linked = Consignment::forDalal($dalal)->where('owner_id', $ownerId)->exists()
            || DalalPartnership::forDalal($dalal)->accepted()->where('owner_id', $ownerId)->exists();

        abort_unless($linked, 404);

        return User::findOrFail($ownerId);
    }
}
