<?php

namespace App\Http\Requests;

use App\Http\Controllers\InformationLookupController;
use Illuminate\Validation\Rule;

class ViewInformationLookupsRequest extends ManageInformationLookupRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['tab' => ['nullable', Rule::in(array_keys(InformationLookupController::TABS))]];
    }
}
