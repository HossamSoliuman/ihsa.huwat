<?php

namespace App\Models;

/** Departments used to place employees in the organisation. */
class Department extends LookupList
{
    public static function title(): string
    {
        return 'الأقسام';
    }
}
