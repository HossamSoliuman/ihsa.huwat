<?php

namespace App\Models;

/** The licensed activity a boat is classified under. */
class BoatClassification extends LookupList
{
    public static function title(): string
    {
        return 'تصنيفات القوارب';
    }
}
