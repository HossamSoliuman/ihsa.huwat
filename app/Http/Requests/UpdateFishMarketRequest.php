<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Unique;

class UpdateFishMarketRequest extends StoreFishMarketRequest
{
    /** Editing a market must not collide with the market being edited. */
    protected function nameIsUniqueInGovernorate(): Unique
    {
        return parent::nameIsUniqueInGovernorate()->ignore($this->route('market'));
    }
}
