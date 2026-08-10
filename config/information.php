<?php

$applicationHost = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST);

/*
 * The desk owns the whole information centre; a moderator is an account the desk creates
 * and pins to a handful of ports, markets, governorates or regions, and sees nothing else.
 */
$deskRoles = ['super_admin', 'quality_supervisor'];
$moderatorRole = 'information_moderator';

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
     * Roles that reach the reference desk unrestricted — the reference lists and the
     * moderator accounts are theirs alone.
     */
    'desk_roles' => $deskRoles,

    /*
     * The role every moderator account is filed under. What one of these accounts reaches
     * is decided entirely by the rows in `user_scopes`, never by the role itself.
     */
    'moderator_role' => $moderatorRole,

    /*
     * Everyone who may open the information centre at all. Signing in on the portal host
     * lands these roles on the desk's own dashboard rather than on the role's dashboard in
     * the main application.
     */
    'admin_roles' => [...$deskRoles, $moderatorRole],

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

    /*
     * PLACEHOLDER. The real job titles inside a fish market shop or auction stall are
     * still with the client; the desk can edit the list, and the seeder leaves edited
     * rows alone, so replacing these values later costs nothing.
     */
    'market_job_titles' => [
        'seller' => 'بائع',
        'cashier' => 'أمين صندوق',
        'accountant' => 'محاسب',
        'supervisor' => 'مشرف',
        'handler' => 'عامل مناولة',
        'cleaner' => 'عامل نظافة',
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
