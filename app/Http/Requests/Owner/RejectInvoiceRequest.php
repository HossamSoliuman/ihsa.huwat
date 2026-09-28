<?php

namespace App\Http\Requests\Owner;

class RejectInvoiceRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => 'سبب الرفض'];
    }
}
