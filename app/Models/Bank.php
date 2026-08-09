<?php

namespace App\Models;

/** Banks available for an employee's salary account. */
class Bank extends LookupList
{
    public static function title(): string
    {
        return 'البنوك';
    }
}
