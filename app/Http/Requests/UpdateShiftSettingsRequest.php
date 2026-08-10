<?php

namespace App\Http\Requests;

class UpdateShiftSettingsRequest extends ManageHumanResourcesSettingsRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'grace_minutes' => ['required', 'integer', 'between:0,1440'],
        ];
    }
}
