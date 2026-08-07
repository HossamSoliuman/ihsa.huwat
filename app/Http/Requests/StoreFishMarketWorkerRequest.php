<?php

namespace App\Http\Requests;

use App\Models\FishMarketWorker;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreFishMarketWorkerRequest extends ManageFishMarketRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->normalizeText($this->input('full_name')),
            'national_id' => $this->normalizeDigits($this->input('national_id')),
            'phone' => $this->normalizePhone($this->input('phone')) ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            /** The crew-member rule, which covers an iqama as well as a national id. */
            'national_id' => ['required', 'regex:/^[12][0-9]{9}$/', $this->identityIsUniqueInUnit()],
            'phone' => ['nullable', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'nationality' => ['required', Rule::in(array_keys(Nationality::options()))],
            'job_title' => ['required', Rule::in(array_keys(MarketJobTitle::options()))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            'national_id.regex' => 'أدخل رقم هوية أو إقامة من 10 أرقام يبدأ بـ 1 أو 2.',
            'national_id.unique' => 'هذه الهوية مسجلة بالفعل على هذه الوحدة.',
            'phone.regex' => 'تحقق من صيغة رقم التلفون.',
            'email.email' => 'أدخل بريداً إلكترونياً صحيحاً.',
            'nationality.in' => 'الجنسية المحددة غير متاحة.',
            'job_title.in' => 'الوظيفة المحددة غير متاحة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'full_name' => 'الاسم',
            'national_id' => 'رقم الهوية',
            'phone' => 'رقم التلفون',
            'email' => 'البريد الإلكتروني',
            'nationality' => 'الجنسية',
            'job_title' => 'الوظيفة',
        ];
    }

    /** One identity per unit — the same person may still work a unit in another market. */
    protected function identityIsUniqueInUnit(): Unique
    {
        return Rule::unique(FishMarketWorker::class, 'national_id')
            ->where('fish_market_unit_id', $this->route('unit')?->getKey());
    }
}
