<?php

namespace Database\Factories;

use App\Models\FishingToolMaterial;

/** @extends LookupListFactory<FishingToolMaterial> */
class FishingToolMaterialFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'مادة أداة';
    }
}
