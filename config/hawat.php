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
     * لوحة الوزارة مقسومة إلى أربع بوابات تتشارك النطاق الرئيسي والتخطيط نفسه:
     * "لوحة الحكومة" التنفيذية تحت البادئة /gov، و"قسم الإحصاء" تحت /stats،
     * و"قسم الإدارة الفرعية" تحت /subadmin، و"المنصة التشغيلية" تحت /admin.
     * App\Support\Nav يختار بينها حسب اسم المسار.
     *
     * لوحات كل قسم لا تظهر إلا في قائمته: البوابة الواحدة تعرض قائمتها وحدها،
     * ولا يُكرَّر التبويب في بوابتين.
     *
     * بوابة المعلومات (مركز الإدارة) ليست منها — لها مضيفها وتخطيطها، وتُوصَل
     * إليها من قائمة قسم الإدارة الفرعية بوصفها أداته المركزية.
     */
    'portals' => [
        'gov' => [
            'label' => 'لوحة الحكومة',
            'icon' => 'shield-check',
            'home' => 'gov.home',
        ],
        'stats' => [
            'label' => 'قسم الإحصاء',
            'icon' => 'database',
            'home' => 'stats.home',
        ],
        'subadmin' => [
            'label' => 'قسم الإدارة الفرعية',
            'icon' => 'network',
            'home' => 'subadmin.home',
        ],
        'ops' => [
            'label' => 'المنصة التشغيلية',
            'icon' => 'layers',
            'home' => 'governorates',
        ],
    ],

    'nav_gov' => [
        [
            'title' => 'عام',
            'items' => [
                ['label' => 'الرئيسية', 'route' => 'gov.home', 'icon' => 'layout-dashboard'],
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
            'title' => 'الاستدامة والرقابة',
            'items' => [
                ['label' => 'الاستدامة والمخزون', 'route' => 'gov.sustainability', 'icon' => 'leaf'],
                ['label' => 'الرقابة والامتثال', 'route' => 'gov.compliance', 'icon' => 'shield-alert'],
            ],
        ],
    ],

    /*
     * قسم الإحصاء — ست عشرة لوحة كانت موزّعة بين البوابتين فجُمعت في بوابة واحدة.
     * ترتيب المجموعات يتبع مسار البيانات: مؤشرات، ثم رصد ميداني واعتماد، ثم تحليل
     * وتقارير، ثم ما بعد الاعتماد من أسواق وأمن غذائي.
     */
    'nav_stats' => [
        [
            'title' => 'المؤشرات واللوحات التنفيذية',
            'items' => [
                ['label' => 'بوابة الإحصاء', 'route' => 'stats.home', 'icon' => 'database'],
                ['label' => 'موجز الإدارة العليا', 'route' => 'stats.executive-briefing', 'icon' => 'crown'],
                ['label' => 'المؤشرات الوطنية', 'route' => 'stats.national-indicators', 'icon' => 'bar-chart'],
                ['label' => 'مقارنة الأداء', 'route' => 'stats.performance-compare', 'icon' => 'gauge'],
            ],
        ],
        [
            'title' => 'الرصد الميداني والاعتماد',
            'items' => [
                ['label' => 'الإحصاء الميداني', 'route' => 'stats.field-statistics', 'icon' => 'clipboard'],
                ['label' => 'المصيد المعتمد', 'route' => 'stats.approved-catch', 'icon' => 'badge-check'],
                ['label' => 'موظفو الإحصاء', 'route' => 'stats.statistics-officers', 'icon' => 'user-cog'],
                ['label' => 'تتبع المصيد', 'route' => 'stats.catch-trace', 'icon' => 'link'],
            ],
        ],
        [
            'title' => 'التحليلات والتقارير',
            'items' => [
                ['label' => 'التحليلات والمؤشرات', 'route' => 'stats.analytics', 'icon' => 'line-chart'],
                ['label' => 'حوات AI', 'route' => 'stats.ai-assistant', 'icon' => 'bot'],
                ['label' => 'التقارير', 'route' => 'stats.reports', 'icon' => 'file-text'],
                ['label' => 'تقارير الإنتاج الشهرية', 'route' => 'stats.monthly-reports', 'icon' => 'file-chart'],
                ['label' => 'النشرة السنوية', 'route' => 'stats.annual-bulletin', 'icon' => 'book-open'],
            ],
        ],
        [
            'title' => 'الأسواق والأمن الغذائي',
            'items' => [
                ['label' => 'الأسواق والمزادات', 'route' => 'stats.markets', 'icon' => 'store'],
                ['label' => 'سلسلة الإمداد', 'route' => 'stats.supply-chain', 'icon' => 'truck'],
                ['label' => 'الأمن الغذائي', 'route' => 'stats.food-security', 'icon' => 'utensils'],
            ],
        ],
    ],

    /*
     * قسم الإدارة الفرعية — ثماني لوحات لإدارة القطاع نفسه لا لبياناته: مركز
     * الإدارة والصلاحيات والهيكل التنظيمي، ثم متابعة المهام والتنبيهات، ثم
     * التدقيق والإنذارات والإعدادات.
     *
     * "مركز الإدارة" يقود إلى بوابة المعلومات على مضيفها المستقل، فاسم مساره
     * بلا بادئة القسم.
     */
    'nav_subadmin' => [
        [
            'title' => 'المدخل',
            'items' => [
                ['label' => 'بوابة الإدارة الفرعية', 'route' => 'subadmin.home', 'icon' => 'network'],
                ['label' => 'مركز الإدارة', 'route' => 'admin.index', 'icon' => 'shield-check'],
            ],
        ],
        [
            'title' => 'الأشخاص والصلاحيات',
            'items' => [
                ['label' => 'المستخدمون والصلاحيات', 'route' => 'subadmin.users', 'icon' => 'user-cog'],
                ['label' => 'الهيكل التنظيمي', 'route' => 'subadmin.org-structure', 'icon' => 'git-branch'],
            ],
        ],
        [
            'title' => 'المهام والتنبيهات',
            'items' => [
                ['label' => 'تقويم المهام الإدارية', 'route' => 'subadmin.admin-tasks', 'icon' => 'calendar-days'],
                ['label' => 'التنبيهات الإدارية', 'route' => 'subadmin.staff-notifications', 'icon' => 'bell-ring'],
            ],
        ],
        [
            'title' => 'التدقيق والإعدادات',
            'items' => [
                ['label' => 'سجل العمليات', 'route' => 'subadmin.audit-log', 'icon' => 'history'],
                ['label' => 'مركز الإنذارات', 'route' => 'subadmin.alerts', 'icon' => 'bell-ring'],
                ['label' => 'الإعدادات', 'route' => 'subadmin.settings', 'icon' => 'settings'],
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
            'title' => 'المراجعة والاستدامة',
            'items' => [
                ['label' => 'مراجعة الفروقات', 'route' => 'discrepancy-review', 'icon' => 'alert-triangle'],
                ['label' => 'الصيد العرضي', 'route' => 'bycatch', 'icon' => 'waves'],
            ],
        ],
    ],
];