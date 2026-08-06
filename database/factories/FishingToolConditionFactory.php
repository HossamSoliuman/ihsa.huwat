<?php

namespace Database\Factories;

use App\Models\FishingToolCondition;

/** @extends LookupListFactory<FishingToolCondition> */
class FishingToolConditionFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'حالة أداة';
    }
}
