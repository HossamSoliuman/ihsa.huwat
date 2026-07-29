<?php

namespace App\Http\Requests;

class StoreCaptainRequest extends DeleteMasterDataRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
