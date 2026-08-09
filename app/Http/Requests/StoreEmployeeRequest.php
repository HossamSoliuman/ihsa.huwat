<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Nationality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:100', 'regex:/\A[A-Za-z0-9._-]+\z/', Rule::unique('users')],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->whereIn('code', [
                    'hr_manager',
                    'finance_officer',
                    'port_supervisor',
                    'stat_employee',
                    'employee_portal',
                ]),
            ],
            'national_id' => ['required', 'string', 'max:20', Rule::unique('employees')],
            'nationality' => ['required', Rule::in(array_keys(Nationality::options()))],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users'), Rule::unique('employees')],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'job_title_id' => ['required', 'integer', Rule::exists('job_titles', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereIn('status', ['active', 'on_leave'])],
            'port_id' => ['nullable', 'integer', Rule::exists('ports', 'id')->where('is_active', true)],
            'hire_date' => ['required', 'date_format:Y-m-d'],
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
