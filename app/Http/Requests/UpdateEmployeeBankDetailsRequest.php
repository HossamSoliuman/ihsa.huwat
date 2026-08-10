<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeBankDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['iban' => strtoupper(str_replace(' ', '', (string) $this->input('iban'))) ?: null]);
    }

    public function rules(): array
    {
        return [
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')->where('is_active', true)],
            'iban' => ['nullable', 'required_with:bank_id', 'string', 'regex:/\A[A-Z]{2}[0-9A-Z]{13,32}\z/'],
            'account_holder_name' => ['nullable', 'required_with:bank_id', 'string', 'max:190'],
        ];
    }
}
