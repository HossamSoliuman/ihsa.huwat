<?php

namespace Database\Factories;

use App\Models\Department;

/** @extends LookupListFactory<Department> */
class DepartmentFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'قسم';
    }
}
