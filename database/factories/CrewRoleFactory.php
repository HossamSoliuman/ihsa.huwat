<?php

namespace Database\Factories;

use App\Models\CrewRole;

/** @extends LookupListFactory<CrewRole> */
class CrewRoleFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'دور بحار';
    }
}
