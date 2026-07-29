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
            'employee_number' => ['required', 'string', 'max:40', Rule::unique('employees')],
            'hire_date' => ['required', 'date_format:Y-m-d'],
            'contract_type' => ['required', Rule::in(['permanent', 'temporary'])],
            'contract_end_date' => ['nullable', 'required_if:contract_type,temporary', 'date_format:Y-m-d', 'after_or_equal:hire_date'],
            'base_salary' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'job_title' => ['required', 'string', 'max:190'],
            'department' => ['nullable', 'string', 'max:190'],
            'job_grade' => ['nullable', 'string', 'max:80'],
            'supervisor_name' => ['nullable', 'string', 'max:190'],
            'supervisor_phone' => ['nullable', 'string', 'max:30'],
            'port_id' => ['nullable', 'required_with:shift_id', Rule::exists('ports', 'id')->where('is_active', true)],
            'shift_id' => ['nullable', 'required_with:port_id', Rule::exists('shifts', 'id')],
        ];
    }
}
