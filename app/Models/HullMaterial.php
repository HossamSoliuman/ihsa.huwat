<?php

namespace App\Models;

/** Materials a hull is built from. */
class HullMaterial extends LookupList
{
    public static function title(): string
    {
        return 'مواد الهيكل';
    }
}
