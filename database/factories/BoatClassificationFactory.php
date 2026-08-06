<?php

namespace Database\Factories;

use App\Models\BoatClassification;

/** @extends LookupListFactory<BoatClassification> */
class BoatClassificationFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'تصنيف قارب';
    }
}
