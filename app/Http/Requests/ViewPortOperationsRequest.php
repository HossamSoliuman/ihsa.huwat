<?php

namespace App\Http\Requests;

use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ViewPortOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Port::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->role->code === 'port_supervisor') {
            $this->merge(['port_id' => $this->user()->port_id]);
        }
    }

    public function rules(): array
    {
        return ['port_id' => ['nullable', 'integer', 'exists:ports,id']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->integer('port_id') > 0 && ! Port::query()->visibleTo($this->user())->whereKey($this->integer('port_id'))->exists()) {
                $validator->errors()->add('port_id', 'الميناء المحدد خارج نطاق صلاحيتك.');
            }
        }];
    }
}
