<?php

namespace App\Http\Requests;

class StorePortRequest extends DeleteMasterDataRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
            'name' => ['required', 'string', 'max:150'],
            'location_name' => ['nullable', 'string', 'max:190'],
            'location_url' => ['nullable', 'url:http,https', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
