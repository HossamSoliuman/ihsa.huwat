<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViewRegionOverviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role->code, ['super_admin', 'region_manager'], true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role->code === 'region_manager') {
            $this->merge(['region_id' => $this->user()->region_id]);
        }
    }

    public function rules(): array
    {
        return ['region_id' => ['nullable', 'integer', 'exists:regions,id']];
    }
}
