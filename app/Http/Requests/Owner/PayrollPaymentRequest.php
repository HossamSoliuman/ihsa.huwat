<?php

namespace App\Http\Requests\Owner;

class PayrollPaymentRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
        ];
    }
}
