<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProvisionEmployeeAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('provision', $this->route('application'));
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:4', 'max:100', 'regex:/\A[A-Za-z0-9._-]+\z/', Rule::unique('users')],
            'password' => ['required', 'max:200', Password::min(10)],
            'hire_date' => ['required', 'date_format:Y-m-d'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'job_title_id' => ['required', 'integer', Rule::exists('job_titles', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereIn('status', ['active', 'on_leave'])],
            'port_id' => ['nullable', 'required_with:shift_id', 'integer', Rule::exists('ports', 'id')->where('is_active', true)],
            'shift_id' => ['nullable', 'required_with:port_id', 'integer', Rule::exists('shifts', 'id')->where('is_active', true)],
            'contract_type' => ['required', Rule::in(['permanent', 'temporary', 'fixed_term', 'part_time', 'seasonal'])],
            'contract_start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:hire_date'],
            'contract_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:contract_start_date'],
            'probation_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:contract_start_date', Rule::when($this->filled('contract_end_date'), 'before_or_equal:contract_end_date')],
            'working_hours_per_day' => ['required', 'numeric', 'between:0.5,24'],
            'working_days_per_week' => ['required', 'integer', 'between:1,7'],
            'base_salary' => ['required', 'numeric', 'between:0.01,99999999.99'],
        ];
    }
}
