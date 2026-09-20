<?php

namespace App\Services\Sales;

use App\Models\AuditLog;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Customer;
use App\Models\PaymentStatus;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Trip;
use App\Models\User;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * بيع المصيد وإرساله للدلال — كلاهما يخصم من دفتر المالك، والإرسال يضيف
 * إلى دفتر الدلال بالوزن نفسه. الوزن المطلوب لا يتجاوز المتاح في الرحلة.
 */
class SaleService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly TripService $trips,
    ) {}

    /**
     * المالك يبيع مصيد رحلة لزبون.
     *
     * @param  array{trip_id:int, customer_id?:int|null, payment_method_id?:int|null, payment_status_id?:int|null, discount?:float, paid_amount?:float|null, notes?:string|null, items:array<int, array{species_id:int, weight_kg:float|string, price_per_kg:float|string}>}  $data
     */
    public function sell(User $seller, array $data): Sale
    {
        $trip = Trip::forOwner($seller)->findOrFail($data['trip_id']);
        $this->assertSellable($trip);

        $items = $this->checkedItems($seller, $trip, $data['items'] ?? []);

        if (! empty($data['customer_id'])) {
            Customer::forAccount($seller)->findOrFail($data['customer_id']);
        }

        return DB::transaction(function () use ($seller, $trip, $data, $items) {
            $subtotal = round(array_sum(array_column($items, 'total')), 2);
            $discount = round((float) ($data['discount'] ?? 0), 2);
            $total = round(max($subtotal - $discount, 0), 2);
            $paid = array_key_exists('paid_amount', $data) && $data['paid_amount'] !== null ? round((float) $data['paid_amount'], 2) : $total;

            $sale = Sale::create([
                'invoice_number' => Sale::nextInvoiceNumber(),
                'seller_id' => $seller->id,
                'trip_id' => $trip->id,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'payment_status_id' => $data['payment_status_id'] ?? $this->paymentStatusFor($paid, $total)->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'owner_net' => $total,
                'status' => Sale::COMPLETED,
                'sold_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                SaleItem::create(['sale_id' => $sale->id] + $item);
                $this->ledger->record($seller, $item['species_id'], StockLedger::SALE, -$item['weight_kg'], $trip, $sale, $seller, "فاتورة {$sale->invoice_number}");
            }

            $this->trips->refreshSaleStatus($trip);

            $this->log('بيع', $seller, $sale->invoice_number, "بيع مصيد الرحلة {$trip->trip_number} بإجمالي {$total}");

            return $sale->load('items.species', 'customer', 'paymentMethod', 'paymentStatus', 'trip');
        });
    }

    /**
     * المالك يرسل مصيد رحلة إلى مخزون دلال.
     *
     * @param  array{trip_id:int, dalal_id:int, notes?:string|null, items:array<int, array{species_id:int, weight_kg:float|string}>}  $data
     */
    public function consign(User $owner, array $data): Consignment
    {
        $trip = Trip::forOwner($owner)->findOrFail($data['trip_id']);
        $this->assertSellable($trip);

        $dalal = User::whereKey($data['dalal_id'])->where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))
            ->first();

        if ($dalal === null) {
            throw ValidationException::withMessages(['dalal_id' => 'اختر دلالًا مفعّلًا.']);
        }

        $items = $this->checkedItems($owner, $trip, $data['items'] ?? [], priced: false);

        return DB::transaction(function () use ($owner, $dalal, $trip, $data, $items) {
            $consignment = Consignment::create([
                'consignment_number' => Consignment::nextNumber(),
                'owner_id' => $owner->id,
                'dalal_id' => $dalal->id,
                'trip_id' => $trip->id,
                'boat_id' => $trip->boat_id,
                'total_kg' => round(array_sum(array_column($items, 'weight_kg')), 2),
                'status' => Consignment::SENT,
                'sent_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                ConsignmentItem::create(['consignment_id' => $consignment->id, 'species_id' => $item['species_id'], 'weight_kg' => $item['weight_kg']]);
                $this->ledger->record($owner, $item['species_id'], StockLedger::CONSIGN_OUT, -$item['weight_kg'], $trip, $consignment, $owner, "إرسال {$consignment->consignment_number} إلى {$dalal->name}");
                $this->ledger->record($dalal, $item['species_id'], StockLedger::CONSIGN_IN, $item['weight_kg'], $trip, $consignment, $owner, "استلام {$consignment->consignment_number} من {$owner->name}");
            }

            $this->trips->refreshSaleStatus($trip);

            $this->log('إرسال لدلال', $owner, $consignment->consignment_number, "إرسال {$consignment->total_kg} كجم من الرحلة {$trip->trip_number} إلى {$dalal->name}");

            return $consignment->load('items.species', 'dalal', 'trip');
        });
    }

    private function assertSellable(Trip $trip): void
    {
        if (! $trip->canSell()) {
            throw ValidationException::withMessages(['trip_id' => 'مصيد هذه الرحلة غير متاح للبيع — لم يكتمل العد أو نفد.']);
        }
    }

    /**
     * يدمج سطور الصنف الواحد ويتحقق أن المطلوب لا يتجاوز المتاح في الرحلة.
     *
     * @return array<int, array{species_id:int, weight_kg:float, price_per_kg?:float, total?:float}>
     */
    private function checkedItems(User $holder, Trip $trip, array $items, bool $priced = true): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'أضف صنفًا واحدًا على الأقل.']);
        }

        $available = $this->ledger->availableByTrip($holder, $trip);
        $merged = [];

        foreach ($items as $index => $item) {
            $id = (int) $item['species_id'];
            $kg = round((float) $item['weight_kg'], 2);

            if ($kg <= 0) {
                throw ValidationException::withMessages(["items.{$index}.weight_kg" => 'الوزن يجب أن يكون أكبر من صفر.']);
            }

            $merged[$id] ??= ['species_id' => $id, 'weight_kg' => 0.0];
            $merged[$id]['weight_kg'] = round($merged[$id]['weight_kg'] + $kg, 2);

            if ($merged[$id]['weight_kg'] > ($available[$id] ?? 0)) {
                throw ValidationException::withMessages([
                    "items.{$index}.weight_kg" => 'الوزن المطلوب يتجاوز المتاح ('.($available[$id] ?? 0).' كجم).',
                ]);
            }

            if ($priced) {
                $price = round((float) ($item['price_per_kg'] ?? 0), 2);
                if ($price <= 0) {
                    throw ValidationException::withMessages(["items.{$index}.price_per_kg" => 'سعر الكيلو يجب أن يكون أكبر من صفر.']);
                }
                // سطور الصنف الواحد بأسعار مختلفة تبقى سطورًا مستقلة في الفاتورة.
                $merged[$id]['lines'][] = ['species_id' => $id, 'weight_kg' => $kg, 'price_per_kg' => $price, 'total' => round($kg * $price, 2)];
            }
        }

        if (! $priced) {
            return array_values($merged);
        }

        return collect($merged)->flatMap(fn ($entry) => $entry['lines'])->values()->all();
    }

    private function paymentStatusFor(float $paid, float $total): PaymentStatus
    {
        return PaymentStatus::named(match (true) {
            $paid <= 0 && $total > 0 => 'غير مدفوع',
            $paid < $total => 'مدفوع جزئيًا',
            default => 'مدفوع',
        });
    }

    private function log(string $action, User $by, string $label, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => 'Sale',
            'record_label' => $label,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
