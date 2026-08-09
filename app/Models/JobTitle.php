<?php

namespace App\Models;

/** Job titles used to place employees in the organisation. */
class JobTitle extends LookupList
{
    public static function title(): string
    {
        return 'المسميات الوظيفية';
    }
}
