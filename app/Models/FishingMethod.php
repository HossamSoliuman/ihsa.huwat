<?php

namespace App\Models;

/** Methods a boat is licensed to fish by. */
class FishingMethod extends LookupList
{
    public static function title(): string
    {
        return 'أساليب الصيد';
    }
}
