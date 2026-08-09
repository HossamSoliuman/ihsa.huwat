<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeSalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateSalary', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        $component = $this->route('salaryComponent');

        return [
            'amount' => [Rule::requiredIf($component?->calculation_type === 'fixed'), 'nullable', 'numeric', 'between:0.01,99999999.99'],
            'percentage' => [Rule::requiredIf($component?->calculation_type === 'percent_of_basic'), 'nullable', 'numeric', 'between:0.01,1000'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
