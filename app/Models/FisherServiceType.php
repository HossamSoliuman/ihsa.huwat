<?php

namespace App\Models;

/**
 * كتالوج خدمات الصيادين — تجديد الرخص وإصدارها والاستبدال ونقل الميناء وغيرها.
 *
 * الخدمة تحمل قسمها المختص، فيُوجَّه الطلب تلقائيًا بلا إدخال يدوي، وتحمل ما
 * يترتب عليها: هل تحتاج موسمًا مرتبطًا، وهل ينتهي مسارها بإصدار رخصة.
 */
class FisherServiceType extends BaseModel
{
    /** الأقسام الأربعة التي قد تملك خدمة. */
    public const SECTIONS = ['الثروة السمكية', 'الإحصاء', 'الإدارة الفرعية', 'الخدمات والتراخيص'];

    protected $casts = [
        'requires_season' => 'boolean',
        'issues_license' => 'boolean',
        'active' => 'boolean',
    ];

    public function requests()
    {
        return $this->hasMany(FisherServiceRequest::class);
    }

    public function staff()
    {
        return $this->belongsToMany(FisherServiceStaff::class, 'fisher_service_staff_type');
    }
}
