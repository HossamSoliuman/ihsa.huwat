<?php

namespace App\Http\Requests;

use App\Models\EmployeeLoan;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EmployeeLoan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'between:0.01,99999999.99'],
            'instalments_count' => ['required', 'integer', 'between:1,120'],
            'first_instalment_month' => ['required', 'date_format:Y-m'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
