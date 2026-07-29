<?php

namespace App\Http\Requests;

use App\Models\EmployeeAssignment;
use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;

class StorePortAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $port = $this->route('port');

        return $port instanceof Port
            && ($this->user()?->can('view', $port) ?? false)
            && ($this->user()?->can('create', EmployeeAssignment::class) ?? false);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'is_temporary' => ['sometimes', 'boolean'],
        ];
    }
}
