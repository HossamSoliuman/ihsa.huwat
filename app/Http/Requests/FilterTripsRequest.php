<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterTripsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Trip::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['date_from' => $this->input('date_from', today()->format('Y-m-d')), 'date_to' => $this->input('date_to', today()->format('Y-m-d'))]);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::in(['expected', 'arrived', 'waiting_employee', 'counting', 'pending_review', 'approved', 'closed'])],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }
}
