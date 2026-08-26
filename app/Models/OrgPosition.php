<?php

namespace App\Models;

class OrgPosition extends BaseModel
{
    protected $casts = [
        'active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function staff()
    {
        return $this->hasMany(OrgStaff::class);
    }

    public function tasks()
    {
        return $this->hasMany(AdminTask::class);
    }

    /**
     * الصلاحيات المكتوبة نصًا مفصولة بفاصلة — تُعرض شارات مستقلة.
     *
     * الفاصلة العربية «،» حرفان بترميز UTF-8، فبلا اللاحقة u يقطع التقسيم
     * الحروف العربية نصفين بدل أن يفصل بين الصلاحيات.
     *
     * @return array<int, string>
     */
    public function authorityList(): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/[,،]/u', (string) $this->authorities) ?: [],
        )));
    }
}
