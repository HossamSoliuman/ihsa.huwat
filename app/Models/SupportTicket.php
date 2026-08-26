<?php

namespace App\Models;

/**
 * تذكرة دعم فني — قناة التواصل الداخلية مع مسؤولي النظام.
 *
 * التذكرة لا تُغلق بلا حلّ مكتوب: من يقرأ السجل بعد شهر يحتاج أن يعرف بماذا
 * انتهت، لا أن حالتها صارت "تم الحل".
 */
class SupportTicket extends BaseModel
{
    public const CATEGORIES = ['مشكلة تقنية', 'استفسار', 'طلب تعديل بيانات', 'اقتراح تطوير', 'صلاحيات الوصول', 'أخرى'];

    public const STATUSES = ['جديدة', 'قيد المعالجة', 'بانتظار رد مقدم الطلب', 'تم الحل', 'مغلقة'];

    /** الحالات النهائية — تذكرة فيها لا تنتظر أحدًا. */
    public const CLOSED = ['تم الحل', 'مغلقة'];

    public const PRIORITIES = ['عادية', 'عاجلة'];

    protected $casts = [
        'submitted_at' => 'datetime',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function assignedStaff()
    {
        return $this->belongsTo(FisherServiceStaff::class, 'assigned_staff_id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, self::CLOSED, true);
    }

    /**
     * الرقم التالي في تسلسل التذاكر — TK-0001 فصاعدًا.
     */
    public static function nextNumber(): string
    {
        $highest = static::pluck('ticket_number')
            ->map(fn ($number) => (int) preg_replace('/\D/', '', (string) $number))
            ->max() ?? 0;

        return 'TK-'.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
