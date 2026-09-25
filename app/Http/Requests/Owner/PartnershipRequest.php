<?php

namespace App\Http\Requests\Owner;

/**
 * طلب المالك التعامل مع دلال: العمولة % والأجور % المقترحتان ورسالة.
 */
class PartnershipRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'commission_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'wage_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['commission_pct' => 'العمولة المقترحة', 'wage_pct' => 'الأجور'];
    }
}
