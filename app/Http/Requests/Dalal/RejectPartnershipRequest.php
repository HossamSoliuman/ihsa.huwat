<?php

namespace App\Http\Requests\Dalal;

class RejectPartnershipRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'response_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
