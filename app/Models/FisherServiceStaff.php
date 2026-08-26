<?php

namespace App\Models;

/**
 * موظف الخدمات — من يستقبل الطلب ويعالجه ويعتمده.
 *
 * لكل موظف قيدان يحدّان ما يراه: تخويل وظيفي بخدمات بعينها، ونطاق جغرافي
 * بميناء أو منطقة. القيدان يعملان معًا في {@see handles()}، والفراغ في أيّهما
 * يعني "بلا قيد" لا "بلا صلاحية" — وإلا لبقيت الطلبات بلا معالج.
 */
class FisherServiceStaff extends BaseModel
{
    protected $table = 'fisher_service_staff';

    public const ROLES = ['مستقبل طلبات', 'معالج', 'مشرف'];

    /** الأقسام التي يجوز إسناد الموظف إليها — تُطابق أقسام المهام الإدارية. */
    public const SECTIONS = ['الثروة السمكية', 'الإحصاء', 'الإدارة الفرعية', 'الخدمات والتراخيص'];

    /** حقل كل صلاحية إجراء بحسب اسمها العربي المعروض. */
    public const PERMISSION_FIELDS = [
        'إنشاء' => 'can_create',
        'معالجة' => 'can_process',
        'اعتماد' => 'can_approve',
        'رفض' => 'can_reject',
        'إسناد' => 'can_assign',
    ];

    protected $casts = [
        'can_create' => 'boolean',
        'can_process' => 'boolean',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'can_assign' => 'boolean',
        'active' => 'boolean',
    ];

    public function assignedPort()
    {
        return $this->belongsTo(Port::class, 'assigned_port_id');
    }

    public function assignedRegion()
    {
        return $this->belongsTo(Region::class, 'assigned_region_id');
    }

    public function serviceTypes()
    {
        return $this->belongsToMany(FisherServiceType::class, 'fisher_service_staff_type');
    }

    public function requests()
    {
        return $this->hasMany(FisherServiceRequest::class, 'assigned_staff_id');
    }

    public function tickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_staff_id');
    }

    /**
     * هل يملك الموظف الصلاحية المطلوبة؟ الموظف المتوقف لا يملك شيئًا.
     */
    public function holds(string $permission): bool
    {
        if (! $this->active) {
            return false;
        }

        $field = self::PERMISSION_FIELDS[$permission] ?? null;

        return $field !== null && (bool) $this->{$field};
    }

    /**
     * وصف التخويل الوظيفي — "الكل" حين لا تُحدَّد خدمات.
     */
    public function handledServicesLabel(): string
    {
        $names = $this->serviceTypes->pluck('name');

        return $names->isEmpty() ? 'الكل' : $names->implode('، ');
    }

    /**
     * وصف النطاق الجغرافي — الميناء أخصّ من المنطقة، وغيابهما تغطية وطنية.
     */
    public function scopeLabel(): string
    {
        return $this->assignedPort?->name
            ?? $this->assignedRegion?->name
            ?? 'كل الموانئ';
    }

    /**
     * هل يقع الطلب داخل تخويل الموظف ونطاقه؟
     *
     * الخدمة أولًا: موظف بلا خدمات محدّدة مخوّل بها كلها. ثم النطاق: ميناء
     * الموظف يُطابق ميناء الطلب، ومنطقته تُطابق منطقة الميناء. طلب بلا ميناء
     * لا يُستبعد — لا معلومة تنفيه.
     */
    public function handles(FisherServiceRequest $request): bool
    {
        if (! $this->active) {
            return false;
        }

        $types = $this->serviceTypes;

        if ($types->isNotEmpty() && ! $types->contains('id', $request->fisher_service_type_id)) {
            return false;
        }

        if ($this->assigned_port_id !== null && $request->port_id !== null && $this->assigned_port_id !== $request->port_id) {
            return false;
        }

        $region = $request->regionId();

        if ($this->assigned_region_id !== null && $region !== null && $this->assigned_region_id !== $region) {
            return false;
        }

        return true;
    }
}
