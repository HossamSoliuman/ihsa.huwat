<?php

namespace Database\Factories;

use App\Models\FishingMethod;

/** @extends LookupListFactory<FishingMethod> */
class FishingMethodFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'أسلوب صيد';
    }
}
