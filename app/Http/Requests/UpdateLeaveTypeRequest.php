<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends ManageHumanResourcesSettingsRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_paid' => $this->boolean('is_paid')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:150'],
            'is_paid' => ['required', 'boolean'],
            'annual_days' => ['nullable', 'numeric', 'between:0,999.9'],
            'payroll_effect' => ['required', Rule::in([
                LeaveType::PAYROLL_NONE,
                LeaveType::PAYROLL_UNPAID_DEDUCTION,
            ])],
            'sort_order' => ['required', 'integer', 'between:0,9999'],
        ];
    }
}
