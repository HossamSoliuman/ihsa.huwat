<?php

namespace App\Http\Requests;

use App\Models\FishMarketUnit;
use Illuminate\Validation\Rule;

class StoreFishMarketUnitRequest extends ManageFishMarketRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => $this->normalizeText($this->input('label')) ?: null,
            'entity_name' => $this->normalizeText($this->input('entity_name')),
            'commercial_registration_no' => $this->normalizeReference($this->input('commercial_registration_no')),
            'municipality_licence_no' => $this->normalizeReference($this->input('municipality_licence_no')) ?: null,
            'tax_number' => $this->normalizeReference($this->input('tax_number')) ?: null,
            'national_address' => $this->normalizeText($this->input('national_address')) ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'unit_type' => ['required', Rule::in(FishMarketUnit::TYPES)],
            'label' => ['nullable', 'string', 'max:100'],
            'entity_name' => ['required', 'string', 'min:3', 'max:190'],
            'commercial_registration_no' => ['required', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'municipality_licence_no' => ['nullable', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'national_address' => ['nullable', 'string', 'max:190'],
            'tax_number' => ['nullable', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            '*.regex' => 'تحقق من صيغة البيانات المدخلة.',
            'unit_type.in' => 'حدد محل بيع سمك أو دكة حراج.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'unit_type' => 'نوع الوحدة',
            'label' => 'التعريف',
            'entity_name' => 'الشركة / المؤسسة',
            'commercial_registration_no' => 'رقم السجل التجاري',
            'municipality_licence_no' => 'رخصة البلدية',
            'national_address' => 'العنوان الوطني',
            'tax_number' => 'الرقم الضريبي',
            'is_active' => 'الحالة',
        ];
    }
}
