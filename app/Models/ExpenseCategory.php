<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قائمة مرجعية — جدول expense_categories، كل فئة في مجموعتها.
 */
class ExpenseCategory extends LookupModel
{
    /** الفئة التي تُرحَّل إليها الصيانة المكتملة تلقائيًا. */
    public const BOAT_MAINTENANCE = 'صيانة القوارب';

    /** الفئة التي يُرحَّل إليها شراء معدات الصيد. */
    public const FISHING_EQUIPMENT = 'معدات صيد';

    protected $table = 'expense_categories';

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExpenseGroup::class, 'expense_group_id');
    }
}
