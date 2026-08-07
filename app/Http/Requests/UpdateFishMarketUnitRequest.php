<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

class UpdateFishMarketUnitRequest extends StoreFishMarketUnitRequest
{
    /**
     * A unit's kind is settled when it is created: it decides which panel the record and
     * its workers belong to, so editing never offers it.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return Arr::except(parent::rules(), ['unit_type']);
    }
}
