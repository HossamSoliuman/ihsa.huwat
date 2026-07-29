<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateHarborCapacitiesRequest extends DeleteHarborRecordRequest
{
    public function rules(): array
    {
        return [
            'capacities' => ['required', 'array:large,small,recreational'],
            'capacities.*.capacity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'capacities.*.status' => ['required', Rule::in(['available', 'stopped'])],
        ];
    }
}
