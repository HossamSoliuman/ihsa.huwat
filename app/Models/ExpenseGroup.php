<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * قائمة مرجعية — جدول expense_groups (عامة، تشغيلية، حكومية، صيانة).
 */
class ExpenseGroup extends LookupModel
{
    public const MAINTENANCE = 'مصروفات الصيانة';

    protected $table = 'expense_groups';

    public function categories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * المجموعات بفئاتها الفعّالة — لقوائم الاختيار المجمّعة (optgroup).
     */
    public static function withCategories()
    {
        return static::query()->active()->ordered()
            ->with(['categories' => fn ($q) => $q->where('active', true)])
            ->get(['id', 'name', 'name_en']);
    }
}
