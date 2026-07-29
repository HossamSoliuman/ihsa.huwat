<?php

namespace App\Http\Requests;

use App\Models\Governorate;
use App\Models\Port;

class StoreHarborRequest extends UpdateHarborRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('create', Port::class)) {
            return false;
        }

        $governorate = Governorate::query()->find($this->integer('governorate_id'));

        return match ($this->user()->role->code) {
            'super_admin' => true,
            'region_manager' => $governorate?->region_id === $this->user()->region_id,
            'gov_supervisor' => $governorate?->id === $this->user()->governorate_id,
            default => false,
        };
    }
}
