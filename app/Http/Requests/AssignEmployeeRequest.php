<?php

namespace App\Http\Requests;

use App\Models\EmployeeAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeAssignment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->whereIn('status', ['active', 'on_leave'])],
            'port_id' => ['required', 'integer', Rule::exists('ports', 'id')->where('is_active', true)],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'assignment_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
