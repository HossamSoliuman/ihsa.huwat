<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * سجل يولّد مصروفًا تلقائيًا (صيانة مكتملة، شراء معدات…) عبر `expenses.source`.
 * ExpenseService::syncSource يُنشئ السند أو يحدّثه أو يلغيه بحسب ما يعيده هنا.
 */
interface ExpenseSource
{
    /**
     * السند المرحَّل من هذا السجل (morphOne على `expenses.source`).
     */
    public function expense(): MorphOne;

    /**
     * ما يُرحَّل الآن، أو null إن لم يعد السجل يولّد مصروفًا.
     *
     * @return array{owner_id: int, category: string, boat_id: ?int, vendor_id?: ?int, date: string, description: string, amount: float}|null
     */
    public function expensePosting(): ?array;

    /**
     * اسم السجل في رسائل المستخدم ("سجل صيانة"، "معدات صيد"…).
     */
    public function expenseSourceLabel(): string;
}
