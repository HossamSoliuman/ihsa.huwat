<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Contracts\ExpenseSource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * مصروفات المالك — منطق وحدة المصروفات في hispa (الخصم مبلغًا أو نسبة،
 * الضريبة، حالة الدفع، ترحيل الصيانة) في مكان واحد يستدعيه الويب والـAPI.
 *
 * الإجمالي = (المبلغ − الخصم) + ضريبة القيمة المضافة على الصافي، وحالة
 * الدفع تُشتق دائمًا من المدفوع مقابل الإجمالي فلا تتعارضان.
 */
class ExpenseService
{
    public const PAID = 'مدفوع';

    public const UNPAID = 'غير مدفوع';

    public const PARTIAL = 'مدفوع جزئيًا';

    public function create(User $owner, array $data, ?UploadedFile $attachment = null): Expense
    {
        return DB::transaction(function () use ($owner, $data, $attachment) {
            $expense = new Expense([
                'expense_number' => Expense::nextNumber(),
                'owner_id' => $owner->id,
                'created_by' => $owner->id,
            ]);

            $this->fill($expense, $data);

            if ($attachment) {
                $expense->attachment_path = $attachment->store("expenses/{$owner->id}", 'public');
            }

            $expense->save();

            $this->log('إضافة مصروف', $owner, $expense, "{$expense->category?->name} بإجمالي {$expense->total}");

            return $expense;
        });
    }

    public function update(User $by, Expense $expense, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Expense
    {
        return DB::transaction(function () use ($by, $expense, $data, $attachment, $removeAttachment) {
            // المصروف المرحَّل يتبع سجله: المبلغ والقارب والتاريخ تُعدَّل من هناك.
            if ($expense->is_automatic) {
                $data = array_merge($data, [
                    'subtotal' => $expense->subtotal,
                    'boat_id' => $expense->boat_id,
                    'trip_id' => $expense->trip_id,
                    'date' => $expense->date->toDateString(),
                ]);
            }

            $this->fill($expense, $data);

            if (($removeAttachment || $attachment) && $expense->attachment_path) {
                Storage::disk('public')->delete($expense->attachment_path);
                $expense->attachment_path = null;
            }

            if ($attachment) {
                $expense->attachment_path = $attachment->store("expenses/{$expense->owner_id}", 'public');
            }

            $expense->save();

            $this->log('تعديل مصروف', $by, $expense, "الإجمالي {$expense->total} — المدفوع {$expense->paid_amount}");

            return $expense;
        });
    }

    public function delete(User $by, Expense $expense): void
    {
        $source = $expense->source;

        if ($source instanceof ExpenseSource && $source->expensePosting() !== null) {
            throw ValidationException::withMessages(['expense' => "هذا المصروف مرحَّل من {$source->expenseSourceLabel()} — عدّله أو احذفه من هناك."]);
        }

        DB::transaction(function () use ($by, $expense) {
            if ($expense->attachment_path) {
                Storage::disk('public')->delete($expense->attachment_path);
            }

            $expense->delete();

            $this->log('حذف مصروف', $by, $expense, "حذف مصروف بإجمالي {$expense->total}");
        });
    }

    /**
     * دفعة على مصروف: لا تتجاوز المتبقي، والحالة تتبعها.
     */
    public function recordPayment(User $by, Expense $expense, float $amount): Expense
    {
        $amount = round($amount, 2);

        if ($amount <= 0 || $amount > $expense->remaining) {
            throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر ولا يتجاوز المتبقي ('.number_format($expense->remaining, 2).' ر.س).']);
        }

        $paid = round($expense->paid_amount + $amount, 2);

        $expense->update([
            'paid_amount' => $paid,
            'payment_status_id' => $this->paymentStatusFor($paid, $expense->total)->id,
        ]);

        $this->log('سداد مصروف', $by, $expense, "سداد {$amount} — المدفوع {$paid} من {$expense->total}");

        return $expense;
    }

    /**
     * ترحيل سجل مولِّد (صيانة مكتملة، شراء معدات…): ما دام يُرحِّل يُنشئ سنده
     * أو يحدّثه (المبلغ والقارب والتاريخ منه، والخصم والضريبة كما ضُبطت على
     * السند)، وإن توقّف أُلغي سنده ما لم يُدفع منه شيء — ما دُفع صُرف فعلًا فيبقى.
     */
    public function syncSource(Model&ExpenseSource $source, User $by): ?Expense
    {
        $expense = $source->expense()->first();
        $posting = $source->expensePosting();

        if ($posting === null) {
            if ($expense && $expense->paid_amount <= 0) {
                $expense->delete();
                $this->log('إلغاء ترحيل', $by, $expense, "حُذف المصروف لأن {$source->expenseSourceLabel()} لم يعد يُرحَّل");

                return null;
            }

            return $expense;
        }

        $expense ??= new Expense([
            'expense_number' => Expense::nextNumber(),
            'owner_id' => $posting['owner_id'],
            'expense_category_id' => ExpenseCategory::named($posting['category'])->id,
            'vendor_id' => $posting['vendor_id'] ?? null,
            'created_by' => $by->id,
        ]);

        $expense->source()->associate($source);
        $expense->fill([
            'boat_id' => $posting['boat_id'],
            'date' => $posting['date'],
            'description' => mb_substr($posting['description'], 0, 255),
        ]);

        $wasNew = ! $expense->exists;
        $this->applyAmounts($expense, $posting['amount'], $expense->discount_pct, $expense->discount ?? 0, $expense->vat_rate ?? 0);
        $this->applyPayment($expense, null, null);
        $expense->save();

        $this->log($wasNew ? 'ترحيل مصروف' : 'تحديث ترحيل مصروف', $by, $expense, "{$source->expenseSourceLabel()}: {$expense->description} بمبلغ {$posting['amount']}");

        return $expense;
    }

    /**
     * قبل حذف السجل المولِّد: سنده غير المدفوع يُحذف، والمدفوع يبقى سندًا يدويًا.
     */
    public function releaseSource(Model&ExpenseSource $source, User $by): void
    {
        $expense = $source->expense()->first();

        if (! $expense) {
            return;
        }

        if ($expense->paid_amount <= 0) {
            $expense->delete();
            $this->log('إلغاء ترحيل', $by, $expense, "حُذف المصروف مع {$source->expenseSourceLabel()}");

            return;
        }

        $expense->update(['source_type' => null, 'source_id' => null]);
    }

    /**
     * @return array{subtotal: float, discount: float, discount_pct: ?float, vat_rate: float, vat_amount: float, total: float}
     */
    public function amounts(float $subtotal, ?float $discountPct, float $discount, float $vatRate): array
    {
        $subtotal = round($subtotal, 2);
        $discount = $discountPct !== null ? round($subtotal * $discountPct / 100, 2) : round($discount, 2);

        if ($discount > $subtotal) {
            throw ValidationException::withMessages(['discount' => 'الخصم لا يتجاوز المبلغ.']);
        }

        $net = $subtotal - $discount;
        $vatAmount = round($net * $vatRate / 100, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_pct' => $discountPct,
            'vat_rate' => round($vatRate, 2),
            'vat_amount' => $vatAmount,
            'total' => round($net + $vatAmount, 2),
        ];
    }

    private function fill(Expense $expense, array $data): void
    {
        $tripId = $data['trip_id'] ?? null;
        $boatId = $data['boat_id'] ?? null;

        // الرحلة تحدد قاربها — لا يُسجَّل مصروف رحلة على قارب آخر.
        if ($tripId) {
            $boatId = Trip::whereKey($tripId)->value('boat_id') ?? $boatId;
        }

        $expense->fill([
            'expense_category_id' => $data['expense_category_id'],
            'boat_id' => $boatId,
            'trip_id' => $tripId,
            'vendor_id' => $data['vendor_id'] ?? null,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'date' => $data['date'],
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->applyAmounts(
            $expense,
            (float) $data['subtotal'],
            isset($data['discount_pct']) && $data['discount_pct'] !== '' ? (float) $data['discount_pct'] : null,
            (float) ($data['discount'] ?? 0),
            (float) ($data['vat_rate'] ?? 0),
        );

        $this->applyPayment($expense, $data['payment_status_id'] ?? null, isset($data['paid_amount']) ? (float) $data['paid_amount'] : null);
    }

    private function applyAmounts(Expense $expense, float $subtotal, ?float $discountPct, float $discount, float $vatRate): void
    {
        $expense->fill($this->amounts($subtotal, $discountPct, $discount, $vatRate));
    }

    /**
     * المدفوع من اختيار الحالة: مدفوع = الإجمالي، غير مدفوع = صفر، جزئي =
     * المبلغ المُدخل. بلا اختيار يبقى المدفوع السابق (مقصوصًا على الإجمالي).
     */
    private function applyPayment(Expense $expense, int|string|null $statusId, ?float $paidAmount): void
    {
        $total = (float) $expense->total;
        $status = $statusId ? PaymentStatus::find($statusId)?->name : null;

        $paid = match ($status) {
            self::PAID => $total,
            self::UNPAID => 0.0,
            self::PARTIAL => round((float) $paidAmount, 2),
            default => min((float) ($expense->paid_amount ?? 0), $total),
        };

        if ($status === self::PARTIAL && ($paid <= 0 || $paid >= $total)) {
            throw ValidationException::withMessages(['paid_amount' => 'المبلغ المدفوع جزئيًا يجب أن يكون أكبر من صفر وأقل من الإجمالي.']);
        }

        $expense->paid_amount = $paid;
        $expense->payment_status_id = $this->paymentStatusFor($paid, $total)->id;
    }

    private function paymentStatusFor(float $paid, float $total): PaymentStatus
    {
        return PaymentStatus::named(match (true) {
            $paid <= 0 && $total > 0 => self::UNPAID,
            $paid < $total => self::PARTIAL,
            default => self::PAID,
        });
    }

    private function log(string $action, User $by, Expense $expense, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => 'Expense',
            'record_label' => $expense->expense_number,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
