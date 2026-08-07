<?php

namespace App\Http\Requests;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use Illuminate\Validation\Rule;

class StoreFishMarketBrokerRequest extends ManageFishMarketRequest
{
    protected function prepareForValidation(): void
    {
        $entityType = (string) $this->input('entity_type');

        $this->merge([
            'full_name' => $this->normalizeText($this->input('full_name')) ?: null,
            'phone' => $this->normalizePhone($this->input('phone')) ?: null,
            'entity_name' => $this->normalizeText($this->input('entity_name')) ?: null,
            'commercial_registration_no' => $this->normalizeReference($this->input('commercial_registration_no')) ?: null,
            'tax_number' => $this->normalizeReference($this->input('tax_number')) ?: null,
            'national_address' => $this->normalizeText($this->input('national_address')) ?: null,
            'is_active' => $this->boolean('is_active'),
            /**
             * The form shows both fieldsets and hides one, so whatever the branch not
             * chosen carried is dropped here rather than filed against the record.
             */
            ...array_fill_keys($this->fieldsOfTheOtherBranch($entityType), null),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fish_market_id' => ['required', 'integer', Rule::exists(FishMarket::class, 'id')],
            'entity_type' => ['required', Rule::in(FishMarketBroker::ENTITY_TYPES)],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'is_active' => ['required', 'boolean'],

            'full_name' => ['required_if:entity_type,individual', 'nullable', 'string', 'min:3', 'max:150'],
            'nationality' => ['required_if:entity_type,individual', 'nullable', Rule::in(array_keys(Nationality::options()))],
            'job_title' => ['required_if:entity_type,individual', 'nullable', Rule::in(array_keys(MarketJobTitle::options()))],

            'entity_name' => ['required_if:entity_type,establishment', 'nullable', 'string', 'min:3', 'max:190'],
            'commercial_registration_no' => ['required_if:entity_type,establishment', 'nullable', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'email' => ['required_if:entity_type,establishment', 'nullable', 'email:rfc', 'max:190'],
            'tax_number' => ['required_if:entity_type,establishment', 'nullable', 'regex:/^[\pL\pN\-\/]{2,60}$/u'],
            'national_address' => ['required_if:entity_type,establishment', 'nullable', 'string', 'max:190'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            '*.required_if' => 'هذا الحقل مطلوب لنوع الدلال المحدد.',
            '*.regex' => 'تحقق من صيغة البيانات المدخلة.',
            'entity_type.in' => 'حدد فرداً أو منشأة.',
            'fish_market_id.exists' => 'السوق المحدد غير معروف.',
            'email.email' => 'أدخل بريداً إلكترونياً صحيحاً.',
            'nationality.in' => 'الجنسية المحددة غير متاحة.',
            'job_title.in' => 'الوظيفة المحددة غير متاحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'fish_market_id' => 'السوق',
            'entity_type' => 'نوع الدلال',
            'full_name' => 'الاسم',
            'nationality' => 'الجنسية',
            'phone' => 'رقم الجوال',
            'job_title' => 'الوظيفة',
            'entity_name' => 'الشركة / المؤسسة',
            'commercial_registration_no' => 'السجل التجاري',
            'email' => 'البريد الإلكتروني',
            'tax_number' => 'الرقم الضريبي',
            'national_address' => 'العنوان الوطني',
            'is_active' => 'الحالة',
        ];
    }

    /**
     * Columns owned by the branch that was not chosen.
     *
     * @return list<string>
     */
    private function fieldsOfTheOtherBranch(string $entityType): array
    {
        return match ($entityType) {
            FishMarketBroker::TYPE_INDIVIDUAL => FishMarketBroker::BRANCH_FIELDS[FishMarketBroker::TYPE_ESTABLISHMENT],
            FishMarketBroker::TYPE_ESTABLISHMENT => FishMarketBroker::BRANCH_FIELDS[FishMarketBroker::TYPE_INDIVIDUAL],
            default => [],
        };
    }
}
