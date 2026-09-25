<?php

namespace App\Http\Requests\Dalal;

use Illuminate\Validation\Rule;

/**
 * بيع من مخزون الدلال: سطر لكل صنف بوزنه وسعره، والرحلة اختيارية — بلا رحلة
 * يُصرف من أقدم ما استُلم من الصنف (SaleService::sellFromStock).
 */
class SaleRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('account_user_id', $this->dalal()->id)],
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.species_id' => ['required', Rule::exists('species', 'id')],
            'items.*.trip_id' => ['nullable', 'integer'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.price_per_kg' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return ['customer_id' => 'الزبون', 'payment_method_id' => 'طريقة الدفع'];
    }
}
