<?php

$applicationHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST);

return [
    /*
     * Dedicated host for the information portal. It defaults to the "info" subdomain
     * of APP_URL — "hawat.sa" serves the portal from "info.hawat.sa" — so no host is
     * hard-coded per environment. INFO_PORTAL_DOMAIN overrides it when the portal
     * lives somewhere else. Should APP_URL carry no host, the portal falls back to
     * "/info" on the main application.
     */
    'domain' => env('INFO_PORTAL_DOMAIN') ?: (is_string($applicationHost) && $applicationHost !== ''
        ? 'info.'.preg_replace('/^www\./', '', $applicationHost)
        : null),

    /*
     * The option lists below are shipped defaults only: each one has a table, a model
     * and a seeder of its own (boat_types → App\Models\BoatType), and the table is what
     * the portal form and the reference desk read at runtime. Edit a list here and it
     * reaches an environment the first time that list's seeder runs; after that the
     * information centre owns it.
     */

    'boat_types' => [
        'small' => 'قارب صغير',
        'large' => 'قارب كبير',
        'recreational' => 'قارب نزهة',
    ],

    'boat_classifications' => [
        'traditional_craft' => 'حرفي تقليدي',
        'commercial_fishing' => 'صيد تجاري',
        'coastal_fishing' => 'صيد ساحلي',
        'recreational_fishing' => 'صيد ترفيهي',
        'support_vessel' => 'قارب خدمات مساندة',
    ],

    'hull_materials' => [
        'wood' => 'خشب',
        'fiberglass' => 'ألياف زجاجية',
        'wood_fiberglass' => 'خشب وألياف زجاجية',
        'steel' => 'حديد',
        'aluminum' => 'ألمنيوم',
        'other' => 'أخرى',
    ],

    'nationalities' => [
        'saudi' => 'سعودي',
        'yemeni' => 'يمني',
        'egyptian' => 'مصري',
        'palestinian' => 'فلسطيني',
        'sudanese' => 'سوداني',
        'indian' => 'هندي',
        'bangladeshi' => 'بنغلاديشي',
        'pakistani' => 'باكستاني',
        'other' => 'أخرى',
    ],

    'crew_roles' => [
        'fisher' => 'صياد',
        'deckhand' => 'بحار سطح',
        'engineer' => 'ميكانيكي / مهندس محرك',
        'cook' => 'طاهٍ',
        'assistant' => 'مساعد',
        'other' => 'أخرى',
    ],

    'fishing_methods' => [
        'nets' => 'الشباك',
        'lines' => 'السنارة والخيوط',
        'traps' => 'القراقير',
        'trawl' => 'الجر',
    ],

    'fishing_tool_types' => [
        'trawl_net' => 'شباك جر',
        'gill_net' => 'شباك خيشومية',
        'traps' => 'قراقير',
        'line' => 'سنارة وخيط',
        'longline' => 'خيط صيد طويل',
        'other' => 'أداة أخرى',
    ],

    'fishing_tool_materials' => [
        'nylon' => 'نايلون',
        'steel' => 'حديد',
        'fiber' => 'ألياف',
        'thread' => 'خيط',
        'rope' => 'حبال',
        'mixed' => 'مواد مختلطة',
        'other' => 'أخرى',
    ],

    'fishing_tool_conditions' => [
        'serviceable' => 'صالح',
        'maintenance_required' => 'يحتاج صيانة',
        'out_of_service' => 'خارج الخدمة',
    ],

    'document_types' => [
        'engine_photo' => [
            'label' => 'صورة المحرك',
            'description' => 'صورة واضحة للوحة بيانات المحرك',
            'required' => true,
            'image_only' => true,
        ],
        'boat_photo' => [
            'label' => 'صورة القارب',
            'description' => 'صورة واضحة وكاملة للقارب',
            'required' => true,
            'image_only' => true,
        ],
        'boat_registration' => [
            'label' => 'استمارة القارب',
            'description' => 'استمارة التسجيل الرسمية',
            'required' => true,
        ],
        'boat_license' => [
            'label' => 'رخصة القارب',
            'description' => 'نسخة سارية من الرخصة',
            'required' => true,
        ],
        'fishing_license' => [
            'label' => 'ترخيص الصيد',
            'description' => 'نسخة من ترخيص مزاولة الصيد',
            'required' => false,
        ],
        'insurance' => [
            'label' => 'التأمين',
            'description' => 'وثيقة التأمين إن وجدت',
            'required' => false,
        ],
        'safety_certificate' => [
            'label' => 'شهادة السلامة',
            'description' => 'شهادة السلامة البحرية',
            'required' => false,
        ],
        'additional' => [
            'label' => 'أي مرفق آخر',
            'description' => 'مستند داعم إضافي',
            'required' => false,
        ],
    ],
];
