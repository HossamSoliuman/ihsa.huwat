<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Unique;

class UpdateFishMarketWorkerRequest extends StoreFishMarketWorkerRequest
{
    /** Editing a worker must not collide with the worker being edited. */
    protected function identityIsUniqueInUnit(): Unique
    {
        return parent::identityIsUniqueInUnit()->ignore($this->route('worker'));
    }
}
