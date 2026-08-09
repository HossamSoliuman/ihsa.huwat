<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPayrollRunPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('markPaid', $this->route('payrollRun')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'payment_reference' => ['required', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
