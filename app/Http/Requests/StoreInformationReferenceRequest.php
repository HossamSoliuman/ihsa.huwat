<?php

namespace App\Http\Requests;

use App\Http\Controllers\InformationLookupController;
use App\Models\FishSpecies;
use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Validation\Rule;

class StoreInformationReferenceRequest extends ManageInformationLookupRequest
{
    protected function prepareForValidation(): void
    {
        if (in_array($this->route('type'), InformationLookupController::ACTIVATABLE, true)) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ($this->route('type')) {
            'governorates' => [
                'region_id' => ['required', 'integer', 'exists:regions,id'],
                'name' => [
                    'required', 'string', 'max:150',
                    Rule::unique(Governorate::class, 'name')->where('region_id', $this->integer('region_id')),
                ],
                'is_active' => ['required', 'boolean'],
            ],
            'ports' => [
                'governorate_id' => ['required', 'integer', 'exists:governorates,id'],
                'name' => ['required', 'string', 'max:150'],
                'location_name' => ['nullable', 'string', 'max:190'],
                'location_url' => ['nullable', 'url:http,https', 'max:500'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'is_active' => ['required', 'boolean'],
            ],
            'species' => ['name_ar' => ['required', 'string', 'max:150', Rule::unique(FishSpecies::class, 'name_ar')]],
            default => [
                'name' => ['required', 'string', 'max:150', Rule::unique(Region::class, 'name')],
                'is_active' => ['required', 'boolean'],
            ],
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['*.unique' => 'هذا السجل مضاف بالفعل.'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'name_ar' => 'الاسم',
            'region_id' => 'المنطقة',
            'governorate_id' => 'المحافظة',
            'location_name' => 'اسم الموقع',
            'location_url' => 'رابط الموقع',
            'latitude' => 'خط العرض',
            'longitude' => 'خط الطول',
        ];
    }
}
