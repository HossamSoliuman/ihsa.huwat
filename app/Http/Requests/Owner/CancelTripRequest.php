<?php

namespace App\Http\Requests\Owner;

class CancelTripRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => 'سبب الإلغاء'];
    }
}
