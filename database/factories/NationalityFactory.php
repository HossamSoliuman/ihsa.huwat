<?php

namespace Database\Factories;

use App\Models\Nationality;

/** @extends LookupListFactory<Nationality> */
class NationalityFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'جنسية';
    }
}
