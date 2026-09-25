<?php

namespace App\Http\Requests\Owner;

use App\Models\BoatMaintenance;
use Illuminate\Validation\Rule;

class MaintenanceRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'boat_id' => ['required', $this->owned('boats')],
            'maintenance_type_id' => ['nullable', $this->lookup('maintenance_types')],
            'date' => ['required', 'date'],
            'technician' => ['nullable', 'string', 'max:255'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(BoatMaintenance::STATUSES)],
        ];
    }

    public function attributes(): array
    {
        return ['boat_id' => 'القارب', 'date' => 'التاريخ'];
    }
}
