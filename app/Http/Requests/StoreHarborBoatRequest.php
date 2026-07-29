<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreHarborBoatRequest extends DeleteHarborRecordRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'registration_no' => ['nullable', 'string', 'max:50', Rule::unique('boats')->ignore($this->route('boat'))],
            'boat_type' => ['required', Rule::in(['large', 'small', 'recreational', 'unclassified'])],
            'harbor_status' => ['required', Rule::in(['occupied', 'disabled', 'inactive', 'unclassified'])],
        ];
    }
}
