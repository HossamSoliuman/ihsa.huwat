<?php

namespace App\Http\Requests;

use App\Models\EmployeeAssignment;
use App\Models\Port;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSubstituteAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeAssignment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role->code === 'port_supervisor') {
            $this->merge(['port_id' => $this->user()->port_id]);
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'port_id' => ['required', 'integer', 'exists:ports,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn (Builder $query) => $query->where('status', 'active')),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! Port::query()->visibleTo($this->user())->whereKey($this->integer('port_id'))->where('is_active', true)->exists()) {
                $validator->errors()->add('port_id', 'الميناء المحدد خارج نطاق صلاحيتك أو غير نشط.');
            }

            if (EmployeeAssignment::query()->where('employee_id', $this->integer('employee_id'))->whereDate('assignment_date', $this->input('date'))->exists()) {
                $validator->errors()->add('employee_id', 'الموظف مسند بالفعل في هذا التاريخ.');
            }
        }];
    }
}
