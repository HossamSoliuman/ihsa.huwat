<?php

namespace App\Http\Requests;

class UpdateFishMarketBrokerRequest extends StoreFishMarketBrokerRequest
{
    /**
     * The branch a broker was filed under stays put — switching فرد to منشأة would strand
     * the fields of the branch left behind. It is merged from the record before the parent
     * decides which side to clear.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['entity_type' => $this->route('broker')->entity_type]);

        parent::prepareForValidation();
    }
}
