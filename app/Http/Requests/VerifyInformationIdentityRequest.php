<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyInformationIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_id' => $this->normalizeDigits($this->input('national_id')),
            'phone' => $this->normalizePhone($this->input('phone')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'national_id' => ['required', 'regex:/^[12][0-9]{9}$/'],
            'phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'national_id.required' => 'أدخل رقم الهوية أو الإقامة.',
            'national_id.regex' => 'رقم الهوية يجب أن يكون 10 أرقام تبدأ بالرقم 1 أو 2.',
            'phone.required' => 'أدخل رقم الجوال.',
            'phone.regex' => 'أدخل رقم جوال صحيحاً، مثل 05xxxxxxxx.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'national_id' => 'رقم الهوية',
            'phone' => 'رقم الجوال',
        ];
    }

    private function normalizeDigits(mixed $value): string
    {
        return strtr((string) $value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    private function normalizePhone(mixed $value): string
    {
        return (string) preg_replace('/[\s()\-]+/', '', $this->normalizeDigits($value));
    }
}
