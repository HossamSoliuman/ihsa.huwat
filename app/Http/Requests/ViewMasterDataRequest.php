<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ViewMasterDataRequest extends DeleteMasterDataRequest
{
    public function rules(): array
    {
        return ['section' => ['nullable', Rule::in(['regions', 'governorates', 'ports', 'boats', 'captains', 'species'])]];
    }
}
