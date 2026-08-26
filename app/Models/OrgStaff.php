<?php

namespace App\Models;

class OrgStaff extends BaseModel
{
    protected $table = 'org_staff';

    protected $casts = [
        'start_date' => 'date',
        'can_create' => 'boolean',
        'can_process' => 'boolean',
        'can_approve' => 'boolean',
        'can_reject' => 'boolean',
        'can_assign' => 'boolean',
    ];

    /** حقل كل صلاحية إجراء بحسب اسمها العربي في نموذج المهمة. */
    public const PERMISSION_FIELDS = [
        'إنشاء' => 'can_create',
        'معالجة' => 'can_process',
        'اعتماد' => 'can_approve',
        'رفض' => 'can_reject',
        'إسناد' => 'can_assign',
    ];

    public function position()
    {
        return $this->belongsTo(OrgPosition::class, 'org_position_id');
    }

    /**
     * هل يملك الموظف الصلاحية المطلوبة لإجراء المهمة؟
     * "أي صلاحية" تكتفي بواحدة، والموظف المتوقف لا يُسند إليه شيء.
     */
    public function holds(string $permission): bool
    {
        if ($this->status === 'متوقف') {
            return false;
        }

        if ($permission === 'أي صلاحية') {
            return collect(self::PERMISSION_FIELDS)->contains(fn (string $field) => (bool) $this->{$field});
        }

        $field = self::PERMISSION_FIELDS[$permission] ?? null;

        return $field !== null && (bool) $this->{$field};
    }
}
