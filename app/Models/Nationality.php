<?php

namespace App\Models;

/** Nationalities offered for the owner, the captain and every crew member. */
class Nationality extends LookupList
{
    public static function title(): string
    {
        return 'الجنسيات';
    }
}
