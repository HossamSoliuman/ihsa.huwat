<?php

namespace App\Http\Requests\Owner;

/**
 * معاينة شهر أو إغلاقه: الشهر (YYYY-MM) وملاحظة اختيارية.
 */
class MonthClosingRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['period' => 'الشهر', 'notes' => 'الملاحظة'];
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function period(): array
    {
        return array_map('intval', explode('-', $this->validated('period')));
    }
}
