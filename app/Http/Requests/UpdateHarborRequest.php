<?php

namespace App\Http\Requests;

use App\Models\Governorate;
use Illuminate\Validation\Validator;

class UpdateHarborRequest extends DeleteHarborRecordRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'governorate_id' => ['required', 'integer', 'exists:governorates,id'], 'name' => ['required', 'string', 'max:150'],
            'location_name' => ['nullable', 'string', 'max:190'], 'location_url' => ['nullable', 'url:http,https', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $governorate = Governorate::query()->find($this->integer('governorate_id'));
            $allowed = match ($this->user()->role->code) {
                'super_admin' => true,
                'region_manager' => $governorate?->region_id === $this->user()->region_id,
                'gov_supervisor' => $governorate?->id === $this->user()->governorate_id,
                'port_supervisor' => $governorate?->id === $this->route('port')?->governorate_id,
                default => false,
            };
            if (! $allowed) {
                $validator->errors()->add('governorate_id', 'المحافظة المحددة خارج نطاق صلاحيتك.');
            }
        }];
    }
}
