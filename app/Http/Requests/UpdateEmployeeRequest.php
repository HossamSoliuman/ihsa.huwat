<?php

namespace App\Http\Requests;

use App\Models\Nationality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('employee')) ?? false;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'national_id' => ['required', 'string', 'max:20', Rule::unique('employees')->ignore($employee)],
            'nationality' => ['required', Rule::in(array_keys(Nationality::options()))],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'max:30'],
            'email' => [
                'required', 'email:rfc', 'max:190',
                Rule::unique('employees')->ignore($employee),
                Rule::unique('users')->ignore($employee->user_id),
            ],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'job_title_id' => ['required', 'integer', Rule::exists('job_titles', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'integer', Rule::notIn([$employee->id]), Rule::exists('employees', 'id')->whereIn('status', ['active', 'on_leave'])],
            'port_id' => ['nullable', 'integer', Rule::exists('ports', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(['active', 'on_leave', 'suspended', 'terminated', 'inactive'])],
            'termination_date' => ['nullable', 'required_if:status,terminated', 'date_format:Y-m-d', 'after_or_equal:'.$employee->hire_date->toDateString()],
            'termination_reason' => ['nullable', 'required_if:status,terminated', 'string', 'max:2000'],
        ];
    }
}
