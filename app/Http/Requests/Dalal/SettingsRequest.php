<?php

namespace App\Http\Requests\Dalal;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * ملف الدلال التجاري ومعلومات شركته — نموذجا الويب يرسل كلٌّ حقوله، فكل حقل
 * اختياري ولا يُحدَّث إلا ما أُرسل.
 */
class SettingsRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'id_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'region_id' => ['sometimes', 'nullable', Rule::exists('regions', 'id')],
            'governorate_id' => ['sometimes', 'nullable', Rule::exists('governorates', 'id')],
            'port_id' => ['sometimes', 'nullable', Rule::exists('ports', 'id')],
            'dakka_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dakka_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cr_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'vat_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'company_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'company_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('company_phone')) {
            $this->merge(['company_phone' => User::normalizePhone($this->input('company_phone'))]);
        }
    }

    public function attributes(): array
    {
        return [
            'cr_number' => 'السجل التجاري',
            'vat_number' => 'الرقم الضريبي',
            'website' => 'الموقع الإلكتروني',
            'company_email' => 'البريد الإلكتروني للشركة',
        ];
    }
}
