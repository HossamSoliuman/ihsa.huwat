<?php

namespace App\Http\Requests;

use App\Models\FishMarket;
use App\Models\Governorate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreFishMarketRequest extends ManageFishMarketRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeText($this->input('name')),
            'investor_name' => $this->normalizeText($this->input('investor_name')),
            'investor_commercial_registration_no' => $this->normalizeReference($this->input('investor_commercial_registration_no')),
            /** Kept null when blank so an optional field never lands as an empty string. */
            'investor_phone' => $this->normalizePhone($this->input('investor_phone')) ?: null,
            'investor_tax_number' => $this->normalizeReference($this->input('investor_tax_number')) ?: null,
            'investor_national_address' => $this->normalizeText($this->input('investor_national_address')) ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'governorate_id' => ['required', 'integer', Rule::exists(Governorate::class, 'id')],
            'name' => [
                'required', 'string', 'min:2', 'max:150',
                $this->nameIsUniqueInGovernorate(),
            ],
            'investor_name' => ['required', 'string', 'min:3', 'max:190'],
            'investor_commercial_registration_no' => ['required', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'investor_phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'investor_email' => ['nullable', 'email:rfc', 'max:190'],
            'investor_tax_number' => ['nullable', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'investor_national_address' => ['nullable', 'string', 'max:190'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            '*.regex' => 'تحقق من صيغة البيانات المدخلة.',
            'name.unique' => 'يوجد سوق بهذا الاسم في المحافظة المحددة.',
            'governorate_id.exists' => 'المحافظة المحددة غير معروفة.',
            'investor_email.email' => 'أدخل بريداً إلكترونياً صحيحاً.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'governorate_id' => 'المحافظة',
            'name' => 'اسم السوق',
            'investor_name' => 'الشركة / المؤسسة المستثمرة',
            'investor_commercial_registration_no' => 'السجل التجاري للمستثمر',
            'investor_phone' => 'تليفون المستثمر',
            'investor_email' => 'البريد الإلكتروني للمستثمر',
            'investor_tax_number' => 'الرقم الضريبي للمستثمر',
            'investor_national_address' => 'العنوان الوطني للمستثمر',
            'is_active' => 'حالة السوق',
        ];
    }

    /** One market name per governorate; the same name may repeat down the coast. */
    protected function nameIsUniqueInGovernorate(): Unique
    {
        return Rule::unique(FishMarket::class, 'name')->where('governorate_id', $this->integer('governorate_id'));
    }
}
