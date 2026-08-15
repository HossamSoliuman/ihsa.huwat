<?php

return [
    /*
     * المضيف الذي تُقدَّم منه لوحة الوزارة. يُشتق من APP_URL تلقائيًا، ولا يُستخدم
     * إلا حين تعمل بوابة المعلومات على مضيف مستقل (انظر config/info.php).
     */
    'domain' => env('APP_DOMAIN') ?: parse_url((string) env('APP_URL'), PHP_URL_HOST),

    'name' => 'حوات',
    'tagline' => 'لوحة وزارة البيئة والمياه والزراعة',
    'ministry' => 'وزارة البيئة والمياه والزراعة',
    'sector' => 'قطاع المصايد البحرية',
    'logo' => 'https://media.base44.com/images/public/6a7a814ea23d8fee1b1c5058/2b4d90b35_logo-arabic-png.png',

    'nav' => [
        [
            'title' => 'عام',
            'items' => [
                ['label' => 'الرئيسية', 'route' => 'home', 'icon' => 'layout-dashboard'],
                ['label' => 'المؤشرات الوطنية', 'route' => 'national-indicators', 'icon' => 'bar-chart'],
                ['label' => 'الخريطة البحرية', 'route' => 'sea-map', 'icon' => 'map'],
                ['label' => 'المحافظات', 'route' => 'governorates', 'icon' => 'building'],
                ['label' => 'المناطق', 'route' => 'regions', 'icon' => 'map'],
            ],
        ],
        [
            'title' => 'الإنتاج والأنواع',
            'items' => [
                ['label' => 'الإنتاج السمكي', 'route' => 'production', 'icon' => 'fish'],
                ['label' => 'الأنواع السمكية', 'route' => 'species', 'icon' => 'fish'],
                ['label' => 'مواسم الصيد', 'route' => 'fishing-seasons', 'icon' => 'calendar'],
                ['label' => 'رخص المواسم', 'route' => 'season-licenses', 'icon' => 'ticket'],
            ],
        ],
        [
            'title' => 'الميدان',
            'items' => [
                ['label' => 'القوارب', 'route' => 'boats', 'icon' => 'ship'],
                ['label' => 'الصيادون', 'route' => 'fishers', 'icon' => 'users'],
                ['label' => 'رحلات الصيد', 'route' => 'trips', 'icon' => 'sailboat'],
                ['label' => 'الجدول الزمني للقوارب', 'route' => 'boat-timeline', 'icon' => 'clock'],
                ['label' => 'الموانئ والمراسي', 'route' => 'ports', 'icon' => 'anchor'],
                ['label' => 'مقارنة الموانئ', 'route' => 'ports-compare', 'icon' => 'git-compare'],
                ['label' => 'مواقع الصيد', 'route' => 'fishing-sites', 'icon' => 'map-pin'],
            ],
        ],
        [
            'title' => 'الإحصاء والاعتماد',
            'items' => [
                ['label' => 'الإحصاء الميداني', 'route' => 'field-statistics', 'icon' => 'clipboard'],
                ['label' => 'المصيد المعتمد', 'route' => 'approved-catch', 'icon' => 'badge-check'],
                ['label' => 'موظفو الإحصاء', 'route' => 'statistics-officers', 'icon' => 'user-cog'],
                ['label' => 'تتبع المصيد', 'route' => 'catch-trace', 'icon' => 'link'],
                ['label' => 'مراجعة الفروقات', 'route' => 'discrepancy-review', 'icon' => 'alert-triangle'],
            ],
        ],
        [
            'title' => 'الاستدامة والرقابة',
            'items' => [
                ['label' => 'الاستدامة والمخزون', 'route' => 'sustainability', 'icon' => 'leaf'],
                ['label' => 'الصيد العرضي', 'route' => 'bycatch', 'icon' => 'waves'],
                ['label' => 'الرقابة والامتثال', 'route' => 'compliance', 'icon' => 'shield-alert'],
            ],
        ],
        [
            'title' => 'السلاسل والأسواق',
            'items' => [
                ['label' => 'الأسواق والمزادات', 'route' => 'markets', 'icon' => 'store'],
                ['label' => 'سلسلة الإمداد', 'route' => 'supply-chain', 'icon' => 'truck'],
                ['label' => 'الأمن الغذائي', 'route' => 'food-security', 'icon' => 'utensils'],
            ],
        ],
        [
            'title' => 'التحليلات والذكاء',
            'items' => [
                ['label' => 'التحليلات والمؤشرات', 'route' => 'analytics', 'icon' => 'line-chart'],
                ['label' => 'حوات AI', 'route' => 'ai-assistant', 'icon' => 'bot'],
                ['label' => 'مركز الإنذارات', 'route' => 'alerts', 'icon' => 'bell-ring'],
            ],
        ],
        [
            'title' => 'النظام',
            'items' => [
                // يقود إلى بوابة المعلومات على مضيفها المستقل (info.hawat.sa).
                ['label' => 'مركز الإدارة', 'route' => 'admin.index', 'icon' => 'shield-check'],
                ['label' => 'التقارير', 'route' => 'reports', 'icon' => 'file-text'],
                ['label' => 'تقارير الإنتاج الشهرية', 'route' => 'monthly-reports', 'icon' => 'file-chart'],
                ['label' => 'النشرة السنوية', 'route' => 'annual-bulletin', 'icon' => 'book-open'],
                ['label' => 'سجل العمليات', 'route' => 'audit-log', 'icon' => 'history'],
                ['label' => 'المستخدمون والصلاحيات', 'route' => 'users', 'icon' => 'user-cog'],
                ['label' => 'الإعدادات', 'route' => 'settings', 'icon' => 'settings'],
            ],
        ],
    ],
];