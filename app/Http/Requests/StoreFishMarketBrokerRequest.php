<?php

namespace App\Http\Requests;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketUnit;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFishMarketBrokerRequest extends ManageFishMarketRequest
{
    protected function prepareForValidation(): void
    {
        $entityType = (string) $this->input('entity_type');

        $this->merge([
            'fish_market_unit_id' => $this->input('fish_market_unit_id') ?: null,
            'stall_number' => $this->normalizeReference($this->input('stall_number')) ?: null,
            'employees' => $this->employeeRows(),
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
            /** A دكة of the same market, and a دكة حراج rather than a محل. */
            'fish_market_unit_id' => ['nullable', 'integer', Rule::exists(FishMarketUnit::class, 'id')
                ->where('fish_market_id', $this->input('fish_market_id'))
                ->where('unit_type', FishMarketUnit::TYPE_AUCTION_STALL)],
            'stall_number' => ['nullable', 'regex:/^[\pL\pN\-\/]{1,20}$/u'],
            'entity_type' => ['required', Rule::in(FishMarketBroker::ENTITY_TYPES)],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'is_active' => ['required', 'boolean'],

            'employees' => ['array', 'max:50'],
            'employees.*' => ['array:job_title,nationality,headcount'],
            'employees.*.job_title' => ['required', Rule::in(array_keys(MarketJobTitle::options()))],
            'employees.*.nationality' => ['required', Rule::in(array_keys(Nationality::options()))],
            'employees.*.headcount' => ['required', 'integer', 'between:1,9999'],

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

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $pairs = collect((array) $this->input('employees', []))
                    ->map(fn (mixed $employee): string => Arr::get((array) $employee, 'job_title')
                        .'|'.Arr::get((array) $employee, 'nationality'));

                if ($pairs->count() !== $pairs->unique()->count()) {
                    $validator->errors()->add('employees', 'الوظيفة والجنسية لا تتكرران في سجلين؛ اجمعهما في عدد واحد.');
                }
            },
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
            'fish_market_unit_id.exists' => 'الدكة المحددة لا تتبع السوق المحدد.',
            'email.email' => 'أدخل بريداً إلكترونياً صحيحاً.',
            'nationality.in' => 'الجنسية المحددة غير متاحة.',
            'job_title.in' => 'الوظيفة المحددة غير متاحة.',
            'employees.max' => 'لا يمكن تسجيل أكثر من 50 سجل موظفين.',
            /** A `*` in a message key spans one segment, so a row's fields are named in full. */
            'employees.*.job_title.required' => 'هذا الحقل مطلوب.',
            'employees.*.job_title.in' => 'الوظيفة المحددة غير متاحة.',
            'employees.*.nationality.required' => 'هذا الحقل مطلوب.',
            'employees.*.nationality.in' => 'الجنسية المحددة غير متاحة.',
            'employees.*.headcount.required' => 'هذا الحقل مطلوب.',
            'employees.*.headcount.integer' => 'أدخل عدداً صحيحاً.',
            'employees.*.headcount.between' => 'أدخل عدداً بين 1 و9999.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'fish_market_id' => 'السوق',
            'fish_market_unit_id' => 'الدكة',
            'stall_number' => 'رقم الدكة',
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
            'employees' => 'الموظفون',
            'employees.*.job_title' => 'نوع الموظف',
            'employees.*.nationality' => 'جنسية الموظف',
            'employees.*.headcount' => 'عدد الموظفين',
        ];
    }

    /**
     * The موظفون block, with the rows the desk added and left empty dropped rather than
     * failing validation as three missing fields.
     *
     * @return list<array{job_title: string, nationality: string, headcount: string}>
     */
    private function employeeRows(): array
    {
        return collect((array) $this->input('employees', []))
            ->map(function (mixed $employee): array {
                $employee = is_array($employee) ? $employee : [];

                return [
                    'job_title' => (string) Arr::get($employee, 'job_title'),
                    'nationality' => (string) Arr::get($employee, 'nationality'),
                    'headcount' => $this->normalizeDigits(Arr::get($employee, 'headcount')),
                ];
            })
            ->reject(fn (array $employee): bool => implode('', $employee) === '')
            ->values()
            ->all();
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
