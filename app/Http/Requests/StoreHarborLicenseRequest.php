<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreHarborLicenseRequest extends DeleteHarborRecordRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['remove_attachment' => $this->boolean('remove_attachment')]);
    }

    public function rules(): array
    {
        return [
            'license_number' => ['required', 'string', 'max:80', Rule::unique('harbor_licenses')->ignore($this->route('license'))],
            'license_type' => ['required', Rule::in(['seasonal', 'operational'])], 'license_holder_name' => ['required', 'string', 'max:190'],
            'boat_number' => ['nullable', 'string', 'max:80'], 'issue_date' => ['nullable', 'date_format:Y-m-d'],
            'expiry_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'license_status' => ['required', Rule::in(['valid', 'expired', 'suspended', 'cancelled'])],
            'attachment' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024)],
            'remove_attachment' => ['boolean'],
        ];
    }
}
