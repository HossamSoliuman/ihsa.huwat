<?php

namespace App\Http\Requests\Owner;

class PayrollLineRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'bonus' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'deduction' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['bonus' => 'الزيادة', 'deduction' => 'الخصم'];
    }
}
