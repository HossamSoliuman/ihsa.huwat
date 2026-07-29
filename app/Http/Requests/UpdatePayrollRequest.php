<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('payroll')) ?? false;
    }

    public function rules(): array
    {
        return [
            'allowances' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'bonuses' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'deductions' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
