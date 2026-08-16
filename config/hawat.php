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

    /*
     * لوحة الوزارة مقسومة إلى بوابتين تتشاركان النطاق الرئيسي والتخطيط نفسه:
     * "لوحة الحكومة" التنفيذية تحت البادئة /gov، و"المنصة التشغيلية" على
     * المسارات العليا. App\Support\Nav يختار بينهما حسب اسم المسار.
     *
     * بوابة المعلومات (مركز الإدارة) ليست منهما — لها مضيفها وتخطيطها.
     */
    'portals' => [
        'gov' => [
            'label' => 'لوحة الحكومة',
            'tagline' => 'اللوحة التنفيذية لقطاع المصايد',
            'icon' => 'shield-check',
            'home' => 'gov.home',
        ],
        'ops' => [
            'label' => 'المنصة التشغيلية',
            'tagline' => 'السجلات والعمليات الميدانية',
            'icon' => 'layers',
            'home' => 'governorates',
        ],
    ],

    'nav_gov' => [
        [
            'title' => 'عام',
            'items' => [
                ['label' => 'الرئيسية', 'route' => 'gov.home', 'icon' => 'layout-dashboard'],
                ['label' => 'المؤشرات الوطنية', 'route' => 'gov.national-indicators', 'icon' => 'bar-chart'],
                ['label' => 'الخريطة البحرية', 'route' => 'gov.sea-map', 'icon' => 'map'],
            ],
        ],
        [
            'title' => 'الإنتاج',
            'items' => [
                ['label' => 'الإنتاج السمكي', 'route' => 'gov.production', 'icon' => 'fish'],
                ['label' => 'مقارنة الموانئ', 'route' => 'gov.ports-compare', 'icon' => 'git-compare'],
            ],
        ],
        [
            'title' => 'الإحصاء والاعتماد',
            'items' => [
                ['label' => 'الإحصاء الميداني', 'route' => 'gov.field-statistics', 'icon' => 'clipboard'],
                ['label' => 'المصيد المعتمد', 'route' => 'gov.approved-catch', 'icon' => 'badge-check'],
            ],
        ],
        [
            'title' => 'الاستدامة والرقابة',
            'items' => [
                ['label' => 'الاستدامة والمخزون', 'route' => 'gov.sustainability', 'icon' => 'leaf'],
                ['label' => 'الرقابة والامتثال', 'route' => 'gov.compliance', 'icon' => 'shield-alert'],
                ['label' => 'الأمن الغذائي', 'route' => 'gov.food-security', 'icon' => 'utensils'],
            ],
        ],
        [
            'title' => 'التحليلات والذكاء',
            'items' => [
                ['label' => 'حوات AI', 'route' => 'gov.ai-assistant', 'icon' => 'bot'],
                ['label' => 'مركز الإنذارات', 'route' => 'gov.alerts', 'icon' => 'bell-ring'],
            ],
        ],
        [
            'title' => 'التقارير',
            'items' => [
                ['label' => 'التقارير', 'route' => 'gov.reports', 'icon' => 'file-text'],
                ['label' => 'تقارير الإنتاج الشهرية', 'route' => 'gov.monthly-reports', 'icon' => 'file-chart'],
                ['label' => 'النشرة السنوية', 'route' => 'gov.annual-bulletin', 'icon' => 'book-open'],
            ],
        ],
    ],

    'nav' => [
        [
            'title' => 'عام',
            'items' => [
                ['label' => 'المحافظات', 'route' => 'governorates', 'icon' => 'building'],
                ['label' => 'المناطق', 'route' => 'regions', 'icon' => 'map'],
            ],
        ],
        [
            'title' => 'الإنتاج والأنواع',
            'items' => [
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
                ['label' => 'مواقع الصيد', 'route' => 'fishing-sites', 'icon' => 'map-pin'],
            ],
        ],
        [
            'title' => 'الإحصاء والاعتماد',
            'items' => [
                ['label' => 'موظفو الإحصاء', 'route' => 'statistics-officers', 'icon' => 'user-cog'],
                ['label' => 'تتبع المصيد', 'route' => 'catch-trace', 'icon' => 'link'],
                ['label' => 'مراجعة الفروقات', 'route' => 'discrepancy-review', 'icon' => 'alert-triangle'],
            ],
        ],
        [
            'title' => 'الاستدامة والرقابة',
            'items' => [
                ['label' => 'الصيد العرضي', 'route' => 'bycatch', 'icon' => 'waves'],
            ],
        ],
        [
            'title' => 'السلاسل والأسواق',
            'items' => [
                ['label' => 'الأسواق والمزادات', 'route' => 'markets', 'icon' => 'store'],
                ['label' => 'سلسلة الإمداد', 'route' => 'supply-chain', 'icon' => 'truck'],
            ],
        ],
        [
            'title' => 'النظام',
            'items' => [
                ['label' => 'التحليلات والمؤشرات', 'route' => 'analytics', 'icon' => 'line-chart'],
                // يقود إلى بوابة المعلومات على مضيفها المستقل (info.hawat.sa).
                ['label' => 'مركز الإدارة', 'route' => 'admin.index', 'icon' => 'shield-check'],
                ['label' => 'سجل العمليات', 'route' => 'audit-log', 'icon' => 'history'],
                ['label' => 'المستخدمون والصلاحيات', 'route' => 'users', 'icon' => 'user-cog'],
                ['label' => 'الإعدادات', 'route' => 'settings', 'icon' => 'settings'],
            ],
        ],
    ],
];