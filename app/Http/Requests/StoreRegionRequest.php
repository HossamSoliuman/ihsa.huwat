<?php

namespace App\Http\Requests;

class StoreRegionRequest extends DeleteMasterDataRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150']];
    }
}
