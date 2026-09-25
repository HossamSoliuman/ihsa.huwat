<?php

namespace App\Http\Requests\Owner;

class AdvanceRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'fisher_id' => ['required', $this->owned('fishers')],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['fisher_id' => 'الفرد', 'date' => 'التاريخ', 'amount' => 'المبلغ'];
    }
}
