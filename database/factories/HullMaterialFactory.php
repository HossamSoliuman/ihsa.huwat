<?php

namespace Database\Factories;

use App\Models\HullMaterial;

/** @extends LookupListFactory<HullMaterial> */
class HullMaterialFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'مادة هيكل';
    }
}
