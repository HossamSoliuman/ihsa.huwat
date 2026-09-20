<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

class EmployeeRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'job_title_id' => ['nullable', $this->lookup('job_titles')],
            'status' => ['nullable', Rule::in(['نشط', 'غير نشط'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
