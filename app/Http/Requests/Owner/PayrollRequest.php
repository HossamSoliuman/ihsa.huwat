<?php

namespace App\Http\Requests\Owner;

/**
 * مسير جديد: قارب وشهر (YYYY-MM).
 */
class PayrollRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'boat_id' => ['required', $this->owned('boats')],
            'period' => ['required', 'date_format:Y-m'],
        ];
    }

    public function attributes(): array
    {
        return ['boat_id' => 'القارب', 'period' => 'الشهر'];
    }
}
