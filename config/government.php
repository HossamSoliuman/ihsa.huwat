<?php

return [
    'allowed_roles' => ['super_admin'],

    'navigation' => [
        ['route' => 'government.dashboard', 'active' => 'government.dashboard', 'label' => 'لوحة التحكم', 'icon' => 'landmark', 'group' => 'نظرة عامة'],
        ['route' => 'government.seasons.index', 'active' => 'government.seasons.*', 'label' => 'إدارة المواسم', 'icon' => 'calendar', 'group' => 'التنظيم البحري'],
    ],

    'season_statuses' => [
        'upcoming' => 'قادم',
        'active' => 'نشط',
        'closed' => 'مغلق',
    ],

    'fishing_tool_options' => [
        'شباك الصيد',
        'الخيط والصنارة',
        'القراقير',
        'السنارات الآلية',
    ],
];
