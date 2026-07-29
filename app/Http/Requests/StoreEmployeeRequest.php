<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
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
            'username' => ['required', 'string', 'max:100', 'alpha_dash:ascii', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'hire_date' => ['required', 'date_format:Y-m-d'],
            'contract_type' => ['required', 'in:permanent,temporary'],
            'contract_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:hire_date', 'required_if:contract_type,temporary'],
        ];
    }
}
