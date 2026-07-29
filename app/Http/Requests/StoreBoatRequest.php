<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreBoatRequest extends DeleteMasterDataRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'registration_no' => ['nullable', 'string', 'max:50', Rule::unique('boats')],
            'boat_type' => ['required', Rule::in(['large', 'small', 'recreational', 'unclassified'])],
            'harbor_status' => ['required', Rule::in(['occupied', 'disabled', 'inactive', 'unclassified'])],
            'home_port_id' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }
}
