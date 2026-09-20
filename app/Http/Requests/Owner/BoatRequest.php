<?php

namespace App\Http\Requests\Owner;

use App\Models\Boat;
use App\Models\Role;
use Illuminate\Validation\Rule;

class BoatRequest extends OwnerRequest
{
    public function rules(): array
    {
        $boat = $this->route('boat');

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'boat_number' => ['required', 'string', 'max:50', Rule::unique('boats', 'boat_number')->ignore($boat)],
            'port_id' => ['required', Rule::exists('ports', 'id')],
            'boat_category_id' => ['nullable', $this->lookup('boat_categories')],
            'boat_type_id' => ['nullable', $this->lookup('boat_types')],
            'captain_id' => ['nullable', Rule::exists('users', 'id')->where('owner_id', $this->owner()->id)->where('role_id', Role::key(Role::CAPTAIN)->id)],
            'status' => ['nullable', Rule::in(Boat::STATUSES)],
            'length_m' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'width_m' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'color' => ['nullable', 'string', 'max:50'],
            'hull_number' => ['nullable', 'string', 'max:100'],
            'body_type' => ['nullable', 'string', 'max:100'],
            'engine_type' => ['nullable', 'string', 'max:100'],
            'engine_power' => ['nullable', 'string', 'max:100'],
            'engine_status' => ['nullable', 'string', 'max:100'],
            'call_sign' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'crew_count' => ['nullable', 'integer', 'min:0'],
            'crew_capacity' => ['nullable', 'integer', 'min:0'],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_status' => ['nullable', 'string', 'max:50'],
            'license_process' => ['nullable', 'string', 'max:100'],
            'license_area' => ['nullable', 'string', 'max:255'],
            'license_date' => ['nullable', 'date'],
            'license_expiry' => ['nullable', 'date'],
            'next_inspection_date' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'اسم القارب', 'boat_number' => 'رقم القارب', 'port_id' => 'الميناء', 'captain_id' => 'الكابتن'];
    }
}
