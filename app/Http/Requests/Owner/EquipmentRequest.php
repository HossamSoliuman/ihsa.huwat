<?php

namespace App\Http\Requests\Owner;

use App\Models\FishingEquipment;
use Illuminate\Validation\Rule;

class EquipmentRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'boat_id' => ['nullable', $this->owned('boats')],
            'gear_type_id' => ['nullable', Rule::exists('gear_types', 'id')],
            'vendor_id' => ['nullable', $this->owned('vendors')],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'condition' => ['nullable', Rule::in(FishingEquipment::CONDITIONS)],
            'season_ids' => ['nullable', 'array'],
            'season_ids.*' => ['integer', Rule::exists('fishing_seasons', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'quantity' => 'الكمية', 'unit_cost' => 'سعر الوحدة', 'boat_id' => 'القارب', 'vendor_id' => 'المورد'];
    }
}
