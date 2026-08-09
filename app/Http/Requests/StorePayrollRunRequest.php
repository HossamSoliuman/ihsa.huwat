<?php

namespace App\Http\Requests;

use App\Models\PayrollRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PayrollRun::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'period_month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('payroll_runs')->where(fn ($query) => $query->where('period_year', $this->integer('period_year'))),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
