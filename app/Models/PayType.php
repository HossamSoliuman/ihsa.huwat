<?php

namespace App\Models;

/**
 * قائمة مرجعية — جدول pay_types: كيف يُحسب أجر فرد الطاقم.
 */
class PayType extends LookupModel
{
    /** يتقاسم نصيب الطاقم من صافي ربح القارب بأسهمه أو بنسبته الخاصة. */
    public const SHARE = 'نسبة من الأرباح';

    /** راتب شهري ثابت يُرحَّل مصروفًا على القارب. */
    public const FIXED = 'راتب ثابت';

    protected $table = 'pay_types';

    public function isShare(): bool
    {
        return $this->name === self::SHARE;
    }
}
