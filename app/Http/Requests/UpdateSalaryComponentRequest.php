<?php

namespace App\Http\Requests;

use App\Models\SalaryComponent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSalaryComponentRequest extends ManageHumanResourcesSettingsRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:150'],
            'component_type' => ['required', Rule::in([
                SalaryComponent::TYPE_EARNING,
                SalaryComponent::TYPE_DEDUCTION,
            ])],
            'calculation_type' => ['required', Rule::in([
                SalaryComponent::CALCULATION_FIXED,
                SalaryComponent::CALCULATION_PERCENT_OF_BASIC,
            ])],
            'sort_order' => ['required', 'integer', 'between:0,9999'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $salaryComponent = $this->route('salaryComponent');

                if (! $salaryComponent instanceof SalaryComponent || ! $salaryComponent->is_basic) {
                    return;
                }

                if ($this->input('component_type') !== SalaryComponent::TYPE_EARNING) {
                    $validator->errors()->add('component_type', 'الراتب الأساسي يجب أن يبقى استحقاقاً.');
                }

                if ($this->input('calculation_type') !== SalaryComponent::CALCULATION_FIXED) {
                    $validator->errors()->add('calculation_type', 'الراتب الأساسي يجب أن يبقى مبلغاً ثابتاً.');
                }
            },
        ];
    }
}
