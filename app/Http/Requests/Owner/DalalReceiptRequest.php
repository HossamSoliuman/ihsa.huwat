<?php

namespace App\Http\Requests\Owner;

class DalalReceiptRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['amount' => 'المبلغ', 'payment_method_id' => 'طريقة الدفع', 'paid_at' => 'تاريخ الاستلام', 'reference' => 'رقم الحوالة / السند'];
    }
}
