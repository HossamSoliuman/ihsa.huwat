<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('payroll')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
