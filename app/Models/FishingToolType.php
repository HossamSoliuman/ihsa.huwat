<?php

namespace App\Models;

/** Types of gear a submission declares — nets, traps, lines. */
class FishingToolType extends LookupList
{
    public static function title(): string
    {
        return 'أنواع أدوات الصيد';
    }
}
