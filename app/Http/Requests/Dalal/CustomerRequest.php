<?php

namespace App\Http\Requests\Dalal;

use Illuminate\Validation\Rule;

class CustomerRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'customer_type_id' => ['nullable', $this->lookup('customer_types')],
            'region_id' => ['nullable', Rule::exists('regions', 'id')],
            'governorate_id' => ['nullable', Rule::exists('governorates', 'id')],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['نشط', 'غير نشط'])],
        ];
    }
}
