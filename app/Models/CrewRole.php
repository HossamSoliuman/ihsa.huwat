<?php

namespace App\Models;

/** Roles a crew member is registered under aboard a boat. */
class CrewRole extends LookupList
{
    public static function title(): string
    {
        return 'أدوار البحارة';
    }
}
