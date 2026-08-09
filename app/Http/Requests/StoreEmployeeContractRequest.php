<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('create', [EmployeeContract::class, $employee]) ?? false);
    }

    public function rules(): array
    {
        return [
            'contract_type' => ['required', Rule::in(['permanent', 'temporary', 'fixed_term', 'part_time', 'seasonal'])],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'probation_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date', Rule::when($this->filled('end_date'), 'before_or_equal:end_date')],
            'working_hours_per_day' => ['required', 'numeric', 'between:0.5,24'],
            'working_days_per_week' => ['required', 'integer', 'between:1,7'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
