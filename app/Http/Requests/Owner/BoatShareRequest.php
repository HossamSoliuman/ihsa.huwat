<?php

namespace App\Http\Requests\Owner;

class BoatShareRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'owner_share_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['owner_share_percent' => 'نسبة المالك'];
    }
}
