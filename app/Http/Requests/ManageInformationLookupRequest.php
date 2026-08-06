<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManageInformationLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-information-lookups');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
