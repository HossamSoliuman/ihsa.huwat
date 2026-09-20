<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

class SaleRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'trip_id' => ['required', $this->owned('trips')],
            'customer_id' => ['nullable', $this->owned('customers', 'account_user_id')],
            'payment_method_id' => ['nullable', $this->lookup('payment_methods')],
            'payment_status_id' => ['nullable', $this->lookup('payment_statuses')],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.species_id' => ['required', Rule::exists('species', 'id')],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.price_per_kg' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return ['trip_id' => 'الرحلة', 'customer_id' => 'الزبون', 'payment_method_id' => 'طريقة الدفع'];
    }
}
