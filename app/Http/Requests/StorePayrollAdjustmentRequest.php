<?php

namespace App\Http\Requests;

use App\Models\PayrollAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PayrollAdjustment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'salary_component_id' => ['nullable', 'integer', Rule::exists('salary_components', 'id')->where('is_active', true)],
            'adjustment_type' => ['required', Rule::in(['earning', 'deduction'])],
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'between:0.01,99999999.99'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
