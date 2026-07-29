<?php

namespace App\Http\Requests;

use App\Models\TripDiscrepancy;
use Illuminate\Foundation\Http\FormRequest;

class FilterDiscrepanciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', TripDiscrepancy::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_from' => $this->input('date_from', today()->subDays(30)->format('Y-m-d')),
            'date_to' => $this->input('date_to', today()->format('Y-m-d')),
        ]);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'], 'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'port_id' => ['nullable', 'integer', 'exists:ports,id'],
        ];
    }
}
