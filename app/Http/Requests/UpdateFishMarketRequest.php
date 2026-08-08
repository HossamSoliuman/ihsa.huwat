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

    /**
     * A market that already exists counts its محلات ودكات by the records on it, so the two
     * opening counts are not asked again and never rebuild the tree underneath it.
     *
     * @return array<string, mixed>
     */
    protected function plannedUnitRules(): array
    {
        return [];
    }
}
