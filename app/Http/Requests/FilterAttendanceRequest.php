<?php

namespace App\Http\Requests;

use App\Models\EmployeeAssignment;
use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FilterAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', EmployeeAssignment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date' => $this->input('date', today()->toDateString()),
            'port_id' => $this->user()?->role->code === 'port_supervisor' ? $this->user()->port_id : $this->input('port_id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $portId = $this->integer('port_id');

            if ($portId > 0 && ! Port::query()->visibleTo($this->user())->whereKey($portId)->exists()) {
                $validator->errors()->add('port_id', 'الميناء المحدد خارج نطاق صلاحيتك.');
            }
        }];
    }
}
