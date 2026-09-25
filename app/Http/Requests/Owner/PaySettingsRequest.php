<?php

namespace App\Http\Requests\Owner;

use App\Models\PayType;
use Illuminate\Validation\Rule;

/**
 * إعداد أجر فرد: راتب ثابت، أو نسبة من الأرباح بأسهم أو بنسبة خاصة.
 */
class PaySettingsRequest extends OwnerRequest
{
    public function rules(): array
    {
        $fixed = fn () => PayType::find($this->input('pay_type_id'))?->name === PayType::FIXED;

        return [
            'pay_type_id' => ['required', $this->lookup('pay_types')],
            'fixed_salary' => [Rule::requiredIf($fixed), 'nullable', 'numeric', 'min:0.01', 'max:9999999'],
            'profit_shares' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'custom_share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['pay_type_id' => 'نوع الأجر', 'fixed_salary' => 'الراتب الشهري', 'profit_shares' => 'الأسهم', 'custom_share_percent' => 'النسبة الخاصة'];
    }
}
