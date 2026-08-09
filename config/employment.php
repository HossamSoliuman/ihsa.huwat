<?php

return [
    'employee_statuses' => ['draft' => 'مسودة', 'active' => 'نشط', 'on_leave' => 'في إجازة', 'suspended' => 'موقوف', 'terminated' => 'منتهي', 'inactive' => 'غير نشط'],
    'contract_types' => ['permanent' => 'دائم', 'temporary' => 'مؤقت', 'fixed_term' => 'محدد المدة', 'part_time' => 'دوام جزئي', 'seasonal' => 'موسمي'],
    'contract_statuses' => ['draft' => 'مسودة', 'active' => 'نشط', 'expired' => 'منتهي', 'terminated' => 'موقوف'],
    'leave_statuses' => ['pending' => 'قيد المراجعة', 'approved' => 'موافق عليها', 'rejected' => 'مرفوضة'],
    'attendance_statuses' => ['present' => 'حاضر', 'late' => 'متأخر', 'absent' => 'غائب', 'on_leave' => 'إجازة'],
    'nationalities' => ['saudi' => 'سعودي', 'non_saudi' => 'غير سعودي'],
    'genders' => ['male' => 'ذكر', 'female' => 'أنثى'],
    'marital_statuses' => ['single' => 'أعزب', 'married' => 'متزوج', 'divorced' => 'مطلق', 'widowed' => 'أرمل'],
    'types' => ['full_time' => 'دوام كامل', 'part_time' => 'دوام جزئي', 'temporary' => 'عمل مؤقت', 'contract' => 'عقد'],
    'job_statuses' => ['draft' => 'مسودة', 'open' => 'متاحة للتقديم', 'closed' => 'مغلقة', 'archived' => 'مؤرشفة'],
    'application_statuses' => [
        'submitted' => 'طلب جديد', 'under_review' => 'قيد المراجعة', 'shortlisted' => 'القائمة المختصرة',
        'interview' => 'مقابلة', 'accepted' => 'مقبول', 'rejected' => 'غير مقبول',
        'account_created' => 'تم إنشاء الحساب', 'withdrawn' => 'منسحب',
    ],
    'identity_types' => ['national_id' => 'هوية وطنية', 'residency' => 'إقامة', 'passport' => 'جواز سفر'],
    'education_levels' => [
        'high_school' => 'ثانوية عامة', 'diploma' => 'دبلوم', 'bachelor' => 'بكالوريوس',
        'master' => 'ماجستير', 'doctorate' => 'دكتوراه', 'other' => 'أخرى',
    ],
    'sources' => ['website' => 'الموقع الإلكتروني', 'social_media' => 'وسائل التواصل', 'referral' => 'ترشيح', 'job_fair' => 'معرض توظيف', 'other' => 'أخرى'],
    'attachment_types' => ['cv' => 'السيرة الذاتية', 'identity' => 'وثيقة الهوية', 'certificate' => 'المؤهل أو الشهادة', 'other' => 'مرفق إضافي'],
    'employee_document_types' => ['national_id' => 'الهوية', 'contract' => 'العقد', 'iban' => 'الآيبان', 'certificate' => 'شهادة', 'other' => 'أخرى'],
    'employee_number_prefix' => 'HWT',
    'contract_number_prefix' => 'HWT-C',

    /** Defaults stay empty until the client supplies its organisation and banking lists. */
    'departments' => [],
    'job_titles' => [],
    'banks' => [],
];
