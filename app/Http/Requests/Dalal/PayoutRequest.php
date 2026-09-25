<?php

namespace App\Http\Requests\Dalal;

class PayoutRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['amount' => 'المبلغ', 'payment_method_id' => 'طريقة الدفع'];
    }
}
