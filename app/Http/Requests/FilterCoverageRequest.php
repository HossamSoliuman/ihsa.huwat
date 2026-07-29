<?php

namespace App\Http\Requests;

use App\Models\EmployeeAssignment;
use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FilterCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', EmployeeAssignment::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role->code === 'region_manager') {
            $this->merge(['region_id' => $this->user()->region_id]);
        }
    }

    public function rules(): array
    {
        return [
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'port_detail' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $portId = $this->integer('port_detail');

            if ($portId > 0 && ! Port::query()->visibleTo($this->user())->whereKey($portId)->exists()) {
                $validator->errors()->add('port_detail', 'الميناء المحدد خارج نطاق صلاحيتك.');
            }
        }];
    }
}
