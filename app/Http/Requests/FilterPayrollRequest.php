<?php

namespace App\Http\Requests;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;

class FilterPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Payroll::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'month' => $this->input('month', today()->month),
            'year' => $this->input('year', today()->year),
        ]);
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ];
    }
}
