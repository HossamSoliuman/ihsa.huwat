<?php

namespace App\Http\Requests;

class StoreGovernorateRequest extends DeleteMasterDataRequest
{
    public function rules(): array
    {
        return ['region_id' => ['required', 'integer', 'exists:regions,id'], 'name' => ['required', 'string', 'max:150']];
    }
}
