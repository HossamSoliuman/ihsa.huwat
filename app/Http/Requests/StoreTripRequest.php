<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Trip::class);
    }

    public function rules(): array
    {
        return [
            'trip_code' => ['required', 'string', 'max:50', Rule::unique('trips')],
            'boat_id' => ['required', 'integer', 'exists:boats,id'],
            'captain_id' => ['required', 'integer', 'exists:captains,id'],
            'port_id' => ['required', 'integer', Rule::exists('ports', 'id')->where('is_active', true)],
            'expected_arrival' => ['nullable', 'date'],
        ];
    }
}
