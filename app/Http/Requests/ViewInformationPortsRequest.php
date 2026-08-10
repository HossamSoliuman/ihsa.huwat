<?php

namespace App\Http\Requests;

use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Validation\Rule;

class ViewInformationPortsRequest extends AccessInformationDeskRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'region_id' => ['nullable', 'integer', Rule::exists(Region::class, 'id')],
            'governorate_id' => ['nullable', 'integer', Rule::exists(Governorate::class, 'id')],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'q' => 'البحث',
            'region_id' => 'المنطقة',
            'governorate_id' => 'المحافظة',
        ];
    }
}
