<?php

namespace Database\Factories;

use App\Models\Bank;

/** @extends LookupListFactory<Bank> */
class BankFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'بنك';
    }
}
