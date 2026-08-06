<?php

namespace Database\Factories;

use App\Models\FishingToolType;

/** @extends LookupListFactory<FishingToolType> */
class FishingToolTypeFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'أداة صيد';
    }
}
