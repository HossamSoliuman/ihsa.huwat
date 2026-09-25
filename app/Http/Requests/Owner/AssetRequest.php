<?php

namespace App\Http\Requests\Owner;

use App\Models\Asset;
use Illuminate\Validation\Rule;

class AssetRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'asset_type_id' => ['required', $this->lookup('asset_types')],
            'boat_id' => ['nullable', $this->owned('boats')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'purchase_cost' => ['required', 'numeric', 'min:0.01'],
            'salvage_value' => ['nullable', 'numeric', 'min:0', 'lte:purchase_cost'],
            'useful_life_years' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in(Asset::STATUSES)],
            'disposed_at' => ['nullable', 'required_unless:status,'.Asset::ACTIVE, 'date', 'after_or_equal:purchase_date'],
            'disposal_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'asset_type_id' => 'نوع الأصل',
            'name' => 'اسم الأصل',
            'purchase_date' => 'تاريخ الشراء',
            'purchase_cost' => 'تكلفة الشراء',
            'salvage_value' => 'قيمة الخردة',
            'useful_life_years' => 'العمر الإنتاجي',
            'disposed_at' => 'تاريخ البيع/التلف',
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'disposed_at.required_unless' => 'حدّد تاريخ البيع أو التلف.',
            'salvage_value.lte' => 'قيمة الخردة لا تتجاوز تكلفة الشراء.',
        ];
    }
}
