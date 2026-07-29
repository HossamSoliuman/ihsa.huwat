<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwapAttendanceShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('assignment')) ?? false;
    }

    public function rules(): array
    {
        return ['shift_id' => ['required', 'integer', 'exists:shifts,id']];
    }
}
