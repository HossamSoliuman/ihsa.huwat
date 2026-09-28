<?php

namespace App\Http\Requests\Dalal;

class InvoiceReplyRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['reply' => 'الرد'];
    }
}
