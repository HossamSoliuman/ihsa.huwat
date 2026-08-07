<?php

namespace App\Models;

/** Jobs a worker is registered under inside a fish market shop or auction stall. */
class MarketJobTitle extends LookupList
{
    public static function title(): string
    {
        return 'الوظائف في الأسواق';
    }
}
