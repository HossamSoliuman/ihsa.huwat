<?php

namespace App\Models;

/**
 * طلب خدمة صياد — من التقديم إلى الاعتماد وإصدار الرخصة.
 *
 * المسار: جديدة ← قيد المعالجة (أو بحاجة مستندات) ← بانتظار الاعتماد ← معتمدة
 * أو مرفوضة. المعالج يقترح رقم الرخصة وتاريخ انتهائها، ولا تصير رخصة فعلية إلا
 * بتوقيع مسؤول مختص في خطوة الاعتماد — لذلك يُفصل الاقتراح عن الإصدار.
 */
class FisherServiceRequest extends BaseModel
{
    public const STATUSES = ['جديدة', 'قيد المعالجة', 'بحاجة مستندات', 'بانتظار الاعتماد', 'معتمدة', 'مرفوضة'];

    /** الحالات التي ما زال الطلب فيها بانتظار معالج. */
    public const OPEN = ['جديدة', 'قيد المعالجة', 'بحاجة مستندات'];

    /** الحالات التي يجوز للمعالج نقل الطلب إليها — الاعتماد ليس منها. */
    public const PROCESSING_STATUSES = ['قيد المعالجة', 'بحاجة مستندات', 'بانتظار الاعتماد'];

    /** الحالات النهائية — لا معالجة بعدها ولا اعتماد. */
    public const CLOSED = ['معتمدة', 'مرفوضة'];

    public const PRIORITIES = ['عادية', 'عاجلة'];

    public const NATIONALITY_TYPES = ['سعودي', 'أجنبي'];

    protected $casts = [
        'birth_date' => 'date',
        'submitted_date' => 'date',
        'processed_date' => 'date',
        'new_license_expiry' => 'date',
        'approved_at' => 'datetime',
    ];

    public function serviceType()
    {
        return $this->belongsTo(FisherServiceType::class, 'fisher_service_type_id');
    }

    public function fisher()
    {
        return $this->belongsTo(Fisher::class);
    }

    public function port()
    {
        return $this->belongsTo(Port::class);
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function fishingSeason()
    {
        return $this->belongsTo(FishingSeason::class);
    }

    public function assignedStaff()
    {
        return $this->belongsTo(FisherServiceStaff::class, 'assigned_staff_id');
    }

    /** العلاقات التي يلزم تحميلها مسبقًا لعرض سطر الطلب كاملًا. */
    public const DISPLAY_RELATIONS = ['serviceType', 'port.governorate.region', 'boat', 'fishingSeason', 'assignedStaff'];

    /**
     * منطقة الطلب — تُقرأ من الميناء صعودًا، فلا تُخزَّن مرتين.
     */
    public function regionId(): ?int
    {
        return $this->port?->governorate?->region_id;
    }

    public function regionName(): ?string
    {
        return $this->port?->governorate?->region?->name;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    /**
     * الرقم التالي في تسلسل الطلبات — SR-0001 فصاعدًا.
     */
    public static function nextNumber(): string
    {
        $highest = static::pluck('request_number')
            ->map(fn ($number) => (int) preg_replace('/\D/', '', (string) $number))
            ->max() ?? 0;

        return 'SR-'.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
