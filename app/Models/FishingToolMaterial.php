<?php

namespace App\Models;

/** Materials a piece of fishing gear is made of. */
class FishingToolMaterial extends LookupList
{
    public static function title(): string
    {
        return 'مواد أدوات الصيد';
    }
}
