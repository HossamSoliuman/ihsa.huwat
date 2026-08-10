<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Nationality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Employee::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'job_title_id' => ['nullable', 'integer', Rule::exists('job_titles', 'id')],
            'port_id' => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'contract_type' => ['nullable', Rule::in(['permanent', 'temporary', 'fixed_term', 'part_time', 'seasonal'])],
            'status' => ['nullable', Rule::in(['draft', 'active', 'on_leave', 'suspended', 'terminated', 'inactive'])],
            'nationality' => ['nullable', Rule::in(array_keys(Nationality::labels()))],
        ];
    }
}
