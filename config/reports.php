<?php

return [
    'types' => [
        'trips' => 'تقرير الرحلات',
        'catch' => 'تقرير المصيد المعتمد',
        'discrepancies' => 'تقرير فروقات المصيد',
        'employees' => 'تقرير أداء الموظفين',
        'ports' => 'تقرير أداء الموانئ',
        'attendance' => 'تقرير الحضور والانصراف',
        'shifts' => 'تقرير المناوبات',
        'leaves' => 'تقرير الإجازات والتكليفات',
        'payroll' => 'تقرير الرواتب',
        'coverage' => 'تقرير التغطية الجغرافية',
        'species' => 'تقرير أنواع الأسماك',
        'boats' => 'تقرير الكباتن والقوارب',
    ],
    'trip_statuses' => [
        'expected' => 'متوقعة', 'arrived' => 'واصلة', 'waiting_employee' => 'بانتظار موظف',
        'counting' => 'تحت الإحصاء', 'pending_review' => 'بانتظار مراجعة',
        'approved' => 'معتمدة', 'closed' => 'مغلقة',
    ],
    'review_statuses' => ['pending' => 'معلقة', 'reviewed' => 'تمت مراجعتها', 'approved' => 'معتمدة'],
    'limit' => 500,
];
