<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

class ConsignmentRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'trip_id' => ['required', $this->owned('trips')],
            'dalal_id' => ['required', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.species_id' => ['required', Rule::exists('species', 'id')],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return ['trip_id' => 'الرحلة', 'dalal_id' => 'الدلال'];
    }
}
