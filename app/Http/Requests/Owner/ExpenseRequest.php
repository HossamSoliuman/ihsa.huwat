<?php

namespace App\Http\Requests\Owner;

use App\Models\Expense;

class ExpenseRequest extends OwnerRequest
{
    /**
     * المصروف المرحَّل من صيانة لا يُرسل حقوله المقفلة (المبلغ، التاريخ،
     * القارب، الرحلة) — تُكمَل من السند نفسه، والخدمة تفرضها على أي حال.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $id = $this->route('expense');
        $expense = $id ? Expense::forOwner($this->owner())->whereKey($id)->whereNotNull('source_type')->first() : null;

        if ($expense) {
            $this->merge([
                'subtotal' => $expense->subtotal,
                'date' => $expense->date->toDateString(),
                'boat_id' => $expense->boat_id,
                'trip_id' => $expense->trip_id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', $this->lookup('expense_categories')],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'boat_id' => ['nullable', $this->owned('boats')],
            'trip_id' => ['nullable', $this->owned('trips')],
            'vendor_id' => ['nullable', $this->owned('vendors')],
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
            'payment_status_id' => ['nullable', $this->lookup('payment_statuses')],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'expense_category_id' => 'الفئة',
            'date' => 'التاريخ',
            'subtotal' => 'المبلغ',
            'discount' => 'الخصم',
            'discount_pct' => 'نسبة الخصم',
            'vat_rate' => 'نسبة الضريبة',
            'boat_id' => 'القارب',
            'trip_id' => 'الرحلة',
            'vendor_id' => 'المورد',
            'paid_amount' => 'المبلغ المدفوع',
            'attachment' => 'المرفق',
        ];
    }
}
