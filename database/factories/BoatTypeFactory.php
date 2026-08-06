<?php

namespace Database\Factories;

use App\Models\BoatType;

/** @extends LookupListFactory<BoatType> */
class BoatTypeFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'نوع قارب';
    }
}
