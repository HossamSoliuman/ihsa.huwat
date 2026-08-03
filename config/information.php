<?php

return [
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

    'captain_qualifications' => [
        'master_fisher' => 'قبطان صيد',
        'experienced_fisher' => 'صياد ذو خبرة',
        'marine_captain' => 'قبطان بحري',
        'assistant_captain' => 'مساعد قبطان',
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
