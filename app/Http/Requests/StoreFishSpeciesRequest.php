<?php

namespace App\Http\Requests;

class StoreFishSpeciesRequest extends DeleteMasterDataRequest
{
    public function rules(): array
    {
        return ['name_ar' => ['required', 'string', 'max:150']];
    }
}
