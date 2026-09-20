<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

class VendorRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['نشط', 'غير نشط'])],
        ];
    }
}
