<?php

namespace App\Models;

/** Service condition a declared piece of gear is in. */
class FishingToolCondition extends LookupList
{
    public static function title(): string
    {
        return 'حالات أدوات الصيد';
    }
}
