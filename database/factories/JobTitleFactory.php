<?php

namespace Database\Factories;

use App\Models\JobTitle;

/** @extends LookupListFactory<JobTitle> */
class JobTitleFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'مسمى وظيفي';
    }
}
