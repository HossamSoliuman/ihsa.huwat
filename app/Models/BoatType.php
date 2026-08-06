<?php

namespace App\Models;

/** The size band a registered boat falls in. */
class BoatType extends LookupList
{
    public static function title(): string
    {
        return 'أنواع القوارب';
    }
}
