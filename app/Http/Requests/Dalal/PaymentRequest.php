<?php

namespace App\Http\Requests\Dalal;

class PaymentRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return ['amount' => 'المبلغ'];
    }
}
