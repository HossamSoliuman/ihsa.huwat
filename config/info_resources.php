<?php

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\BusinessGlossaryTerm;
use App\Models\DataCatalogAsset;
use App\Models\DataLineageEdge;
use App\Models\DataQualityIssue;
use App\Models\FaoStandardMapping;
use App\Models\Fisher;
use App\Models\FishingSeason;
use App\Models\FishingSite;
use App\Models\GearType;
use App\Models\Governorate;
use App\Models\KpiRegistry;
use App\Models\Market;
use App\Models\MarketAuction;
use App\Models\Port;
use App\Models\Region;
use App\Models\SeasonLicense;
use App\Models\Species;
use App\Models\StatisticsOfficer;
use App\Models\UiTranslation;
use App\Models\UserPermission;

/*
 * تعريف موارد بوابة المعلومات فوق مخطط لوحة الوزارة العلائقي.
 *
 * الحقول المرتبطة بمفتاح أجنبي تُعرَّف بـ `options_from` مع `value` و`label`: تُخزَّن
 * القيمة (id) وتُعرض التسمية. أعمدة الجدول تستخدم المسار النقطي (مثل `port.name`)
 * وتُحمَّل مسبقًا عبر `with` تفاديًا لاستعلامات N+1.
 */

$portOptions = ['model' => Port::class, 'value' => 'id', 'label' => 'name'];
$boatOptions = ['model' => Boat::class, 'value' => 'id', 'label' => 'name'];
$marketOptions = ['model' => Market::class, 'value' => 'id', 'label' => 'name'];
$speciesOptions = ['model' => Species::class, 'value' => 'id', 'label' => 'name_ar'];

$statusOptions = ['نشط', 'متوقف', 'صيانة'];
$pressureOptions = ['طبيعي', 'منخفض', 'متوسط', 'مرتفع'];
$refreshOptions = ['لحظي', 'كل ساعة', 'يومي', 'شهري', 'سنوي', 'عند الطلب', 'غير منطبق'];
$domainOptions = ['Geography', 'Fleet', 'Species', 'Trips', 'Catch', 'Licenses', 'Fishers', 'Markets', 'Compliance', 'Analytics', 'GIS', 'AI', 'FAO', 'Other'];

return [

    'regions' => [
        'label' => 'المناطق',
        'model' => Region::class,
        'title' => 'المناطق',
        'description' => 'المناطق الساحلية الرئيسية.',
        'columns' => ['name' => 'الاسم', 'code' => 'الرمز', 'coast_length_km' => 'طول الساحل (كم)', 'ports_count' => 'عدد الموانئ', 'total_catch_tons' => 'المصيد (طن)', 'status' => 'الحالة'],
        'badges' => ['status' => ['نشط' => 'ok', 'متوقف' => 'warn']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'code', 'label' => 'الرمز'],
            ['key' => 'coast_length_km', 'label' => 'طول الساحل (كم)', 'type' => 'number'],
            ['key' => 'governorates_count', 'label' => 'عدد المحافظات', 'type' => 'number'],
            ['key' => 'ports_count', 'label' => 'عدد الموانئ', 'type' => 'number'],
            ['key' => 'total_catch_tons', 'label' => 'إجمالي المصيد (طن)', 'type' => 'number'],
            ['key' => 'active_boats', 'label' => 'القوارب النشطة', 'type' => 'number'],
            ['key' => 'active_fishers', 'label' => 'الصيادون النشطون', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف']],
        ],
    ],

    'governorates' => [
        'label' => 'المحافظات',
        'model' => Governorate::class,
        'title' => 'المحافظات',
        'description' => 'المحافظات الساحلية وربطها بالمناطق.',
        'with' => ['region'],
        'columns' => ['name' => 'الاسم', 'code' => 'الرمز', 'region.name' => 'المنطقة', 'coastal' => 'ساحلية', 'ports_count' => 'عدد الموانئ', 'status' => 'الحالة'],
        'badges' => ['status' => ['نشط' => 'ok', 'متوقف' => 'warn']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'code', 'label' => 'الرمز'],
            ['key' => 'region_id', 'label' => 'المنطقة', 'type' => 'select', 'required' => true, 'options_from' => ['model' => Region::class, 'value' => 'id', 'label' => 'name']],
            ['key' => 'coastal', 'label' => 'ساحلية', 'type' => 'boolean'],
            ['key' => 'ports_count', 'label' => 'عدد الموانئ', 'type' => 'number'],
            ['key' => 'total_catch_tons', 'label' => 'إجمالي المصيد (طن)', 'type' => 'number'],
            ['key' => 'active_boats', 'label' => 'القوارب النشطة', 'type' => 'number'],
            ['key' => 'active_fishers', 'label' => 'الصيادون النشطون', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف']],
        ],
    ],

    'ports' => [
        'label' => 'الموانئ',
        'model' => Port::class,
        'title' => 'الموانئ والمراسي',
        'description' => 'إدارة الموانئ وربطها بالمحافظة.',
        'with' => ['governorate.region'],
        'columns' => ['name' => 'الاسم', 'code' => 'الرمز', 'governorate.region.name' => 'المنطقة', 'governorate.name' => 'المحافظة', 'boats_count' => 'القوارب', 'status' => 'الحالة'],
        'badges' => ['status' => ['نشط' => 'ok', 'متوقف' => 'warn', 'صيانة' => 'warn']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'code', 'label' => 'الرمز'],
            ['key' => 'governorate_id', 'label' => 'المحافظة', 'type' => 'select', 'required' => true, 'options_from' => ['model' => Governorate::class, 'value' => 'id', 'label' => 'name']],
            ['key' => 'lat', 'label' => 'خط العرض', 'type' => 'number'],
            ['key' => 'lng', 'label' => 'خط الطول', 'type' => 'number'],
            ['key' => 'boats_count', 'label' => 'عدد القوارب', 'type' => 'number'],
            ['key' => 'active_boats', 'label' => 'القوارب النشطة', 'type' => 'number'],
            ['key' => 'fishers_count', 'label' => 'عدد الصيادين', 'type' => 'number'],
            ['key' => 'daily_trips', 'label' => 'الرحلات اليومية', 'type' => 'number'],
            ['key' => 'monthly_trips', 'label' => 'الرحلات الشهرية', 'type' => 'number'],
            ['key' => 'total_catch_tons', 'label' => 'إجمالي المصيد (طن)', 'type' => 'number'],
            ['key' => 'statistics_staff', 'label' => 'موظفو الإحصاء', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => $statusOptions],
        ],
    ],

    'fishing-sites' => [
        'label' => 'مواقع الصيد',
        'model' => FishingSite::class,
        'title' => 'مواقع الصيد',
        'description' => 'مواقع الصيد وإحداثياتها وأقرب ميناء.',
        'with' => ['port'],
        'columns' => ['name' => 'الاسم', 'site_type' => 'النوع', 'port.name' => 'أقرب ميناء', 'depth_m' => 'العمق (م)', 'catch_kg' => 'المصيد (كجم)', 'pressure_level' => 'مستوى الضغط'],
        'badges' => ['pressure_level' => ['طبيعي' => 'ok', 'منخفض' => 'ok', 'متوسط' => 'warn', 'مرتفع' => 'danger']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'port_id', 'label' => 'أقرب ميناء', 'type' => 'select', 'required' => true, 'options_from' => $portOptions],
            ['key' => 'site_type', 'label' => 'نوع الموقع', 'type' => 'select', 'options' => ['شعاب مرجانية', 'مياه ضحلة', 'جزر', 'خليج', 'مياه عميقة']],
            ['key' => 'lat', 'label' => 'خط العرض', 'type' => 'number'],
            ['key' => 'lng', 'label' => 'خط الطول', 'type' => 'number'],
            ['key' => 'depth_m', 'label' => 'العمق (م)', 'type' => 'number'],
            ['key' => 'trips_count', 'label' => 'عدد الرحلات', 'type' => 'number'],
            ['key' => 'boats_count', 'label' => 'عدد القوارب', 'type' => 'number'],
            ['key' => 'catch_kg', 'label' => 'كمية المصيد (كجم)', 'type' => 'number'],
            ['key' => 'avg_catch_per_trip', 'label' => 'متوسط المصيد/رحلة', 'type' => 'number'],
            ['key' => 'pressure_level', 'label' => 'مستوى الضغط', 'type' => 'select', 'options' => $pressureOptions],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف']],
        ],
    ],

    'boats' => [
        'label' => 'القوارب',
        'model' => Boat::class,
        'title' => 'القوارب',
        'description' => 'أسطول الصيد ورخصه وحالته التشغيلية.',
        'with' => ['port'],
        'columns' => ['name' => 'الاسم', 'boat_number' => 'رقم القارب', 'port.name' => 'الميناء', 'captain' => 'الكابتن', 'license_status' => 'الرخصة', 'status' => 'الحالة'],
        'badges' => [
            'license_status' => ['سارية' => 'ok', 'قريبة الانتهاء' => 'warn', 'منتهية' => 'danger'],
            'status' => ['نشط' => 'ok', 'صيانة' => 'warn', 'متوقف' => 'warn', 'محظور' => 'danger'],
        ],
        'fields' => [
            ['key' => 'name', 'label' => 'اسم القارب', 'required' => true],
            ['key' => 'boat_number', 'label' => 'رقم القارب', 'required' => true],
            ['key' => 'port_id', 'label' => 'الميناء', 'type' => 'select', 'required' => true, 'options_from' => $portOptions],
            ['key' => 'owner', 'label' => 'المالك'],
            ['key' => 'captain', 'label' => 'الكابتن'],
            ['key' => 'boat_type', 'label' => 'نوع القارب', 'type' => 'select', 'options' => ['قارب تقليدي', 'لنش', 'طراد', 'سفينة جر']],
            ['key' => 'length_m', 'label' => 'الطول (م)', 'type' => 'number'],
            ['key' => 'crew_count', 'label' => 'عدد الطاقم', 'type' => 'number'],
            ['key' => 'crew_capacity', 'label' => 'سعة الطاقم', 'type' => 'number'],
            ['key' => 'license_type', 'label' => 'نوع الرخصة', 'type' => 'select', 'options' => ['حرفي', 'تجاري', 'ترفيهي']],
            ['key' => 'license_number', 'label' => 'رقم الرخصة'],
            ['key' => 'license_expiry', 'label' => 'انتهاء الرخصة', 'type' => 'date'],
            ['key' => 'license_status', 'label' => 'حالة الرخصة', 'type' => 'select', 'options' => ['سارية', 'قريبة الانتهاء', 'منتهية']],
            ['key' => 'trips_count', 'label' => 'عدد الرحلات', 'type' => 'number'],
            ['key' => 'total_catch_kg', 'label' => 'إجمالي المصيد (كجم)', 'type' => 'number'],
            ['key' => 'violations_count', 'label' => 'عدد المخالفات', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف', 'محظور', 'صيانة']],
        ],
    ],

    'fishers' => [
        'label' => 'الصيادون',
        'model' => Fisher::class,
        'title' => 'الصيادون',
        'description' => 'سجل الصيادين والكباتن وربطهم بالقوارب والموانئ.',
        'with' => ['port', 'boat'],
        'columns' => ['name' => 'الاسم', 'national_id' => 'رقم الهوية', 'role' => 'الدور', 'port.name' => 'الميناء', 'boat.name' => 'القارب', 'status' => 'الحالة'],
        'badges' => ['status' => ['نشط' => 'ok', 'متوقف' => 'warn', 'موقوف' => 'danger']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'national_id', 'label' => 'رقم الهوية', 'required' => true],
            ['key' => 'phone', 'label' => 'الجوال'],
            ['key' => 'role', 'label' => 'الدور', 'type' => 'select', 'options' => ['صياد', 'كابتن', 'بحّار']],
            ['key' => 'port_id', 'label' => 'الميناء', 'type' => 'select', 'required' => true, 'options_from' => $portOptions],
            ['key' => 'boat_id', 'label' => 'القارب', 'type' => 'select', 'options_from' => $boatOptions],
            ['key' => 'license_number', 'label' => 'رقم الرخصة'],
            ['key' => 'license_status', 'label' => 'حالة الرخصة', 'type' => 'select', 'options' => ['سارية', 'قريبة الانتهاء', 'منتهية']],
            ['key' => 'license_expiry', 'label' => 'انتهاء الرخصة', 'type' => 'date'],
            ['key' => 'experience_years', 'label' => 'سنوات الخبرة', 'type' => 'number'],
            ['key' => 'trips_count', 'label' => 'عدد الرحلات', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف', 'موقوف']],
        ],
    ],

    'species' => [
        'label' => 'الأنواع السمكية',
        'model' => Species::class,
        'title' => 'الأنواع السمكية',
        'description' => 'دليل الأنواع المدقق مع الأسماء العلمية والمحلية وحالة المخزون.',
        'columns' => ['code' => 'الترميز', 'name_ar' => 'الاسم المحلي', 'name_sci' => 'الاسم العلمي', 'category' => 'التصنيف', 'status' => 'حالة المخزون'],
        'badges' => ['status' => ['مستقر' => 'ok', 'مراقبة' => 'warn', 'ضغط صيد مرتفع' => 'danger', 'انخفاض حاد' => 'danger']],
        'fields' => [
            ['key' => 'code', 'label' => 'الترميز', 'type' => 'number'],
            ['key' => 'name_ar', 'label' => 'الاسم المحلي', 'required' => true],
            ['key' => 'name_sci', 'label' => 'الاسم العلمي'],
            ['key' => 'corrected_name_sci', 'label' => 'الاسم العلمي المصحّح'],
            ['key' => 'name_en', 'label' => 'الاسم الإنجليزي'],
            ['key' => 'name_local_gulf', 'label' => 'الاسم المحلي – الخليج العربي'],
            ['key' => 'name_local_red_sea', 'label' => 'الاسم المحلي – البحر الأحمر'],
            ['key' => 'record_type', 'label' => 'نوع السجل', 'type' => 'select', 'options' => ['نوع سمك', 'عائلة/مجموعة']],
            ['key' => 'category', 'label' => 'التصنيف', 'type' => 'select', 'options' => ['أسماك', 'روبيان', 'قشريات', 'رخويات', 'أخرى']],
            ['key' => 'avg_weight_kg', 'label' => 'متوسط الوزن (كجم)', 'type' => 'number'],
            ['key' => 'avg_length_cm', 'label' => 'متوسط الحجم (سم)', 'type' => 'number'],
            ['key' => 'season', 'label' => 'موسم الصيد'],
            ['key' => 'regions', 'label' => 'مناطق الانتشار'],
            ['key' => 'top_port', 'label' => 'أكثر ميناء إنزالاً', 'type' => 'select', 'options_from' => ['model' => Port::class, 'column' => 'name']],
            ['key' => 'status', 'label' => 'حالة المخزون', 'type' => 'select', 'options' => ['مستقر', 'مراقبة', 'ضغط صيد مرتفع', 'انخفاض حاد']],
            ['key' => 'directory_status', 'label' => 'حالة الدليل', 'type' => 'select', 'options' => ['نشط', 'غير نشط']],
            ['key' => 'review_status', 'label' => 'حالة المراجعة العلمية', 'type' => 'select', 'options' => ['مصحح وموثق', 'منسق آليًا', 'مقبول مبدئيًا']],
            ['key' => 'source_1', 'label' => 'المصدر 1'],
            ['key' => 'source_2', 'label' => 'المصدر 2'],
            ['key' => 'review_date', 'label' => 'تاريخ المراجعة', 'type' => 'date'],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'gear-types' => [
        'label' => 'أدوات الصيد',
        'model' => GearType::class,
        'title' => 'أدوات الصيد',
        'description' => 'أنواع أدوات الصيد وترميز ISSCFG ومواصفاتها.',
        'columns' => ['name' => 'الاسم', 'code' => 'الرمز', 'category' => 'التصنيف', 'isscfg_code' => 'ISSCFG', 'selectivity' => 'الانتقائية', 'status' => 'الحالة'],
        'badges' => ['selectivity' => ['عالية' => 'ok', 'متوسطة' => 'warn', 'منخفضة' => 'danger']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'code', 'label' => 'الرمز'],
            ['key' => 'category', 'label' => 'التصنيف', 'type' => 'select', 'options' => ['شباك', 'خيوط', 'فخاخ', 'جر', 'أخرى']],
            ['key' => 'isscfg_code', 'label' => 'كود ISSCFG'],
            ['key' => 'min_mesh_size_mm', 'label' => 'أصغر فتحة عين (مم)', 'type' => 'number'],
            ['key' => 'selectivity', 'label' => 'الانتقائية', 'type' => 'select', 'options' => ['عالية', 'متوسطة', 'منخفضة']],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'statistics-officers' => [
        'label' => 'موظفو الإحصاء',
        'model' => StatisticsOfficer::class,
        'title' => 'موظفو الإحصاء',
        'description' => 'فريق الإحصاء الميداني وتوزيعهم على الموانئ والورديات.',
        'with' => ['port'],
        'columns' => ['name' => 'الاسم', 'employee_number' => 'الرقم الوظيفي', 'port.name' => 'الميناء', 'shift' => 'الوردية', 'trips_counted' => 'رحلات محصاة', 'status' => 'الحالة'],
        'badges' => ['status' => ['نشط' => 'ok', 'متوقف' => 'warn']],
        'fields' => [
            ['key' => 'name', 'label' => 'الاسم', 'required' => true],
            ['key' => 'employee_number', 'label' => 'الرقم الوظيفي', 'required' => true],
            ['key' => 'email', 'label' => 'البريد الإلكتروني'],
            ['key' => 'phone', 'label' => 'الجوال'],
            ['key' => 'port_id', 'label' => 'الميناء', 'type' => 'select', 'required' => true, 'options_from' => $portOptions],
            ['key' => 'shift', 'label' => 'الوردية', 'type' => 'select', 'options' => ['صباحية', 'مسائية', 'ليلية']],
            ['key' => 'trips_counted', 'label' => 'الرحلات المحصاة', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متوقف']],
        ],
    ],

    'fishing-seasons' => [
        'label' => 'مواسم الصيد',
        'model' => FishingSeason::class,
        'title' => 'مواسم الصيد',
        'description' => 'مواسم الصيد وفترات الحظر والحصص المسموحة.',
        'columns' => ['name' => 'الموسم', 'species' => 'النوع', 'region' => 'المنطقة', 'start_date' => 'من', 'end_date' => 'إلى', 'status' => 'الحالة'],
        'badges' => ['status' => ['مفتوح' => 'ok', 'مغلق' => 'danger', 'قريب الفتح' => 'warn', 'موقوف مؤقتاً' => 'warn']],
        'fields' => [
            ['key' => 'name', 'label' => 'اسم الموسم', 'required' => true],
            ['key' => 'species', 'label' => 'النوع', 'type' => 'select', 'required' => true, 'options_from' => ['model' => Species::class, 'column' => 'name_ar']],
            ['key' => 'sea', 'label' => 'البحر', 'type' => 'select', 'options' => ['الخليج العربي', 'البحر الأحمر']],
            ['key' => 'region', 'label' => 'المنطقة', 'type' => 'select', 'options_from' => ['model' => Region::class, 'column' => 'name']],
            ['key' => 'season_type', 'label' => 'نوع الفترة', 'type' => 'select', 'options' => ['موسم صيد', 'فترة حظر']],
            ['key' => 'start_month', 'label' => 'شهر البداية', 'type' => 'number'],
            ['key' => 'end_month', 'label' => 'شهر النهاية', 'type' => 'number'],
            ['key' => 'start_date', 'label' => 'تاريخ البداية', 'type' => 'date'],
            ['key' => 'end_date', 'label' => 'تاريخ النهاية', 'type' => 'date'],
            ['key' => 'ban_start_date', 'label' => 'بداية الحظر', 'type' => 'date'],
            ['key' => 'ban_end_date', 'label' => 'نهاية الحظر', 'type' => 'date'],
            ['key' => 'gear', 'label' => 'الأدوات المسموحة'],
            ['key' => 'gear_type', 'label' => 'أداة الصيد', 'type' => 'select', 'options_from' => ['model' => GearType::class, 'column' => 'name']],
            ['key' => 'license_type', 'label' => 'نوع الرخصة'],
            ['key' => 'licenses_max', 'label' => 'الحد الأقصى للرخص', 'type' => 'number'],
            ['key' => 'boats_count', 'label' => 'عدد القوارب', 'type' => 'number'],
            ['key' => 'min_size_cm', 'label' => 'أصغر حجم مسموح (سم)', 'type' => 'number'],
            ['key' => 'allowed_areas', 'label' => 'المناطق المسموحة', 'type' => 'textarea'],
            ['key' => 'prohibited_areas', 'label' => 'المناطق المحظورة', 'type' => 'textarea'],
            ['key' => 'authority', 'label' => 'الجهة المصدرة'],
            ['key' => 'decision_number', 'label' => 'رقم القرار'],
            ['key' => 'decision_date', 'label' => 'تاريخ القرار', 'type' => 'date'],
            ['key' => 'quota_tons', 'label' => 'الحصة (طن)', 'type' => 'number'],
            ['key' => 'used_quota_tons', 'label' => 'المستهلك (طن)', 'type' => 'number'],
            ['key' => 'approval_status', 'label' => 'حالة الاعتماد', 'type' => 'select', 'options' => ['مسودة', 'قيد المراجعة', 'معتمد', 'منشور']],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['مفتوح', 'مغلق', 'قريب الفتح', 'موقوف مؤقتاً']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'season-licenses' => [
        'label' => 'رخص المواسم',
        'model' => SeasonLicense::class,
        'title' => 'رخص المواسم',
        'description' => 'رخص الصيد الموسمية المرتبطة بالقوارب والحصص.',
        'with' => ['fishingSeason', 'boat', 'port'],
        'columns' => ['license_number' => 'رقم الرخصة', 'fishingSeason.name' => 'الموسم', 'boat.name' => 'القارب', 'species' => 'النوع', 'quota_kg' => 'الحصة (كجم)', 'status' => 'الحالة'],
        'badges' => ['status' => ['سارية' => 'ok', 'منتهية' => 'danger', 'ملغاة' => 'danger', 'معلقة' => 'warn']],
        'fields' => [
            ['key' => 'license_number', 'label' => 'رقم الرخصة', 'required' => true],
            ['key' => 'fishing_season_id', 'label' => 'الموسم', 'type' => 'select', 'required' => true, 'options_from' => ['model' => FishingSeason::class, 'value' => 'id', 'label' => 'name']],
            ['key' => 'boat_id', 'label' => 'القارب', 'type' => 'select', 'options_from' => $boatOptions],
            ['key' => 'boat_name', 'label' => 'اسم القارب', 'required' => true],
            ['key' => 'fisher_name', 'label' => 'اسم الصياد'],
            ['key' => 'captain', 'label' => 'الكابتن'],
            ['key' => 'holder_name', 'label' => 'اسم صاحب الرخصة'],
            ['key' => 'species', 'label' => 'النوع', 'type' => 'select', 'options_from' => ['model' => Species::class, 'column' => 'name_ar']],
            ['key' => 'port_id', 'label' => 'الميناء', 'type' => 'select', 'options_from' => $portOptions],
            ['key' => 'gear_type', 'label' => 'أداة الصيد', 'type' => 'select', 'options_from' => ['model' => GearType::class, 'column' => 'name']],
            ['key' => 'allowed_area', 'label' => 'المنطقة المسموحة', 'type' => 'textarea'],
            ['key' => 'issue_date', 'label' => 'تاريخ الإصدار', 'type' => 'date'],
            ['key' => 'expiry_date', 'label' => 'تاريخ الانتهاء', 'type' => 'date'],
            ['key' => 'quota_kg', 'label' => 'الحصة (كجم)', 'type' => 'number'],
            ['key' => 'used_kg', 'label' => 'المستخدم (كجم)', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['سارية', 'منتهية', 'ملغاة', 'معلقة']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'markets' => [
        'label' => 'الأسواق',
        'model' => Market::class,
        'title' => 'أسواق السمك',
        'description' => 'أسواق ومزادات السمك وربطها بالموانئ.',
        'columns' => ['name' => 'اسم السوق', 'region' => 'المنطقة', 'governorate' => 'المحافظة', 'market_type' => 'النوع', 'auction_stalls_count' => 'دكات المزاد', 'status' => 'الحالة'],
        'badges' => ['status' => ['نشط' => 'ok', 'متعطل' => 'warn', 'مغلق' => 'danger']],
        'fields' => [
            ['key' => 'name', 'label' => 'اسم السوق', 'required' => true],
            ['key' => 'code', 'label' => 'رمز السوق'],
            ['key' => 'region', 'label' => 'المنطقة', 'type' => 'select', 'required' => true, 'options_from' => ['model' => Region::class, 'column' => 'name']],
            ['key' => 'governorate', 'label' => 'المحافظة', 'type' => 'select', 'required' => true, 'options_from' => ['model' => Governorate::class, 'column' => 'name']],
            ['key' => 'port', 'label' => 'الميناء', 'type' => 'select', 'options_from' => ['model' => Port::class, 'column' => 'name']],
            ['key' => 'market_type', 'label' => 'نوع السوق', 'type' => 'select', 'options' => ['مزاد', 'جملة', 'تجزئة', 'مركّب']],
            ['key' => 'fish_shops_count', 'label' => 'عدد محلات بيع السمك', 'type' => 'number'],
            ['key' => 'auction_stalls_count', 'label' => 'عدد دكات المزادات', 'type' => 'number'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['نشط', 'متعطل', 'مغلق']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'market-auctions' => [
        'label' => 'المزادات',
        'model' => MarketAuction::class,
        'title' => 'حركة المزادات',
        'description' => 'سجل المزادات اليومية والأسعار والكميات المبيعة.',
        'with' => ['market', 'species'],
        'columns' => ['auction_date' => 'التاريخ', 'market.name' => 'السوق', 'species.name_ar' => 'النوع', 'quantity_sold_kg' => 'المبيع (كجم)', 'avg_price_per_kg' => 'متوسط السعر', 'buyer_type' => 'نوع المشتري'],
        'fields' => [
            ['key' => 'market_id', 'label' => 'السوق', 'type' => 'select', 'required' => true, 'options_from' => $marketOptions],
            ['key' => 'species_id', 'label' => 'النوع', 'type' => 'select', 'required' => true, 'options_from' => $speciesOptions],
            ['key' => 'auction_date', 'label' => 'التاريخ', 'type' => 'date', 'required' => true],
            ['key' => 'grade', 'label' => 'الدرجة', 'type' => 'select', 'options' => ['ممتاز', 'أولى', 'ثانية']],
            ['key' => 'quantity_offered_kg', 'label' => 'المعروض (كجم)', 'type' => 'number'],
            ['key' => 'quantity_sold_kg', 'label' => 'المبيع (كجم)', 'type' => 'number'],
            ['key' => 'min_price_per_kg', 'label' => 'أدنى سعر', 'type' => 'number'],
            ['key' => 'max_price_per_kg', 'label' => 'أعلى سعر', 'type' => 'number'],
            ['key' => 'avg_price_per_kg', 'label' => 'متوسط السعر', 'type' => 'number'],
            ['key' => 'buyer_type', 'label' => 'نوع المشتري', 'type' => 'select', 'options' => ['تجزئة', 'جملة', 'مطاعم', 'تصدير']],
            ['key' => 'source_port', 'label' => 'ميناء المصدر', 'type' => 'select', 'options_from' => ['model' => Port::class, 'column' => 'name']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'user-permissions' => [
        'label' => 'صلاحيات المستخدمين',
        'model' => UserPermission::class,
        'title' => 'صلاحيات المستخدمين',
        'description' => 'الأدوار والنطاق الجغرافي المسموح لكل مستخدم.',
        'columns' => ['user_email' => 'البريد', 'full_name' => 'الاسم', 'role' => 'الدور', 'scope_level' => 'النطاق', 'region' => 'المنطقة', 'port' => 'الميناء'],
        'fields' => [
            ['key' => 'user_email', 'label' => 'البريد الإلكتروني', 'required' => true],
            ['key' => 'full_name', 'label' => 'الاسم الكامل'],
            ['key' => 'role', 'label' => 'الدور', 'type' => 'select', 'options' => ['admin', 'top_management', 'fisheries_admin', 'researcher', 'supervision', 'region_manager', 'governorate_manager', 'port_manager', 'user']],
            ['key' => 'scope_level', 'label' => 'مستوى النطاق', 'type' => 'select', 'options' => ['المملكة', 'المنطقة', 'المحافظة', 'الميناء']],
            ['key' => 'region', 'label' => 'المنطقة', 'type' => 'select', 'options_from' => ['model' => Region::class, 'column' => 'name']],
            ['key' => 'governorate', 'label' => 'المحافظة', 'type' => 'select', 'options_from' => ['model' => Governorate::class, 'column' => 'name']],
            ['key' => 'port', 'label' => 'الميناء', 'type' => 'select', 'options_from' => ['model' => Port::class, 'column' => 'name']],
            ['key' => 'can_approve', 'label' => 'صلاحية الاعتماد', 'type' => 'boolean'],
            ['key' => 'can_export', 'label' => 'صلاحية التصدير', 'type' => 'boolean'],
            ['key' => 'active', 'label' => 'نشط', 'type' => 'boolean'],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'ui-translations' => [
        'label' => 'ترجمة الواجهة',
        'model' => UiTranslation::class,
        'title' => 'ترجمة الواجهة',
        'description' => 'كتالوج النصوص العربية ومقابلها الإنجليزي.',
        'columns' => ['key' => 'المفتاح', 'ar' => 'النص العربي', 'en' => 'الترجمة الإنجليزية', 'context' => 'السياق', 'status' => 'الحالة'],
        'badges' => ['status' => ['مترجم' => 'ok', 'بحاجة مراجعة' => 'warn', 'مسودة' => 'warn']],
        'fields' => [
            ['key' => 'key', 'label' => 'المفتاح', 'required' => true],
            ['key' => 'ar', 'label' => 'النص العربي', 'type' => 'textarea', 'required' => true],
            ['key' => 'en', 'label' => 'الترجمة الإنجليزية', 'type' => 'textarea'],
            ['key' => 'context', 'label' => 'السياق'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['مترجم', 'بحاجة مراجعة', 'مسودة']],
        ],
    ],

    'fao-mappings' => [
        'label' => 'ربط معايير FAO',
        'model' => FaoStandardMapping::class,
        'title' => 'ربط معايير FAO',
        'description' => 'مطابقة العناصر المحلية مع أكواد ASFIS وISSCAAP وISSCFG وغيرها.',
        'columns' => ['local_entity_type' => 'نوع العنصر', 'local_name_ar' => 'الاسم المحلي', 'fao_system' => 'المعيار', 'fao_code' => 'كود FAO', 'verification_status' => 'التحقق'],
        'badges' => ['verification_status' => ['محقق' => 'ok', 'مقترح' => 'warn', 'غير محقق' => 'danger', 'يحتاج مراجعة' => 'warn']],
        'fields' => [
            ['key' => 'local_entity_type', 'label' => 'نوع العنصر المحلي', 'type' => 'select', 'options' => ['Species', 'FishingSite', 'Boat', 'FishingGear', 'Port', 'Commodity', 'Other'], 'required' => true],
            ['key' => 'local_id', 'label' => 'المعرّف المحلي'],
            ['key' => 'local_name_ar', 'label' => 'الاسم العربي'],
            ['key' => 'local_name_en', 'label' => 'الاسم الإنجليزي'],
            ['key' => 'fao_system', 'label' => 'معيار FAO', 'type' => 'select', 'options' => ['ASFIS', 'ISSCAAP', 'FAO_MAJOR_FISHING_AREA', 'ISSCFG', 'ISSCFV', 'ISSCFC', 'PSMA', 'FIRMS', 'OTHER'], 'required' => true],
            ['key' => 'fao_code', 'label' => 'كود FAO'],
            ['key' => 'fao_name', 'label' => 'اسم FAO'],
            ['key' => 'scientific_name', 'label' => 'الاسم العلمي'],
            ['key' => 'source_url', 'label' => 'رابط المصدر'],
            ['key' => 'verification_status', 'label' => 'حالة التحقق', 'type' => 'select', 'options' => ['غير محقق', 'مقترح', 'محقق', 'يحتاج مراجعة']],
            ['key' => 'valid_from', 'label' => 'ساري من', 'type' => 'date'],
            ['key' => 'valid_to', 'label' => 'ساري إلى', 'type' => 'date'],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'data-quality-issues' => [
        'label' => 'ملاحظات الجودة',
        'model' => DataQualityIssue::class,
        'title' => 'ملاحظات جودة البيانات',
        'description' => 'تذاكر الجودة المكتشفة ومسار معالجتها.',
        'columns' => ['entity_name' => 'الكيان', 'record_label' => 'السجل', 'category' => 'الفئة', 'severity' => 'الخطورة', 'status' => 'الحالة', 'assigned_to' => 'المسؤول'],
        'badges' => ['severity' => ['critical' => 'danger', 'warning' => 'warn']],
        'fields' => [
            ['key' => 'fingerprint', 'label' => 'البصمة الفنية'],
            ['key' => 'run_id', 'label' => 'معرّف الفحص'],
            ['key' => 'category', 'label' => 'الفئة', 'type' => 'select', 'options' => ['duplicate', 'relationship', 'completeness', 'geography', 'traceability', 'other'], 'required' => true],
            ['key' => 'severity', 'label' => 'الخطورة', 'type' => 'select', 'options' => ['warning', 'critical']],
            ['key' => 'entity_name', 'label' => 'الكيان', 'required' => true],
            ['key' => 'record_id', 'label' => 'معرّف السجل'],
            ['key' => 'record_label', 'label' => 'السجل'],
            ['key' => 'field_name', 'label' => 'الحقل'],
            ['key' => 'issue_message', 'label' => 'المشكلة', 'type' => 'textarea', 'required' => true],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['مفتوحة', 'قيد المعالجة', 'بانتظار التحقق', 'تم الحل', 'استثناء مقبول']],
            ['key' => 'priority', 'label' => 'الأولوية', 'type' => 'select', 'options' => ['منخفضة', 'متوسطة', 'عالية', 'حرجة']],
            ['key' => 'assigned_to', 'label' => 'مسندة إلى'],
            ['key' => 'due_date', 'label' => 'تاريخ الاستحقاق', 'type' => 'date'],
            ['key' => 'resolution_note', 'label' => 'ملاحظة الحل', 'type' => 'textarea'],
        ],
    ],

    'catalog-assets' => [
        'label' => 'أصول البيانات',
        'model' => DataCatalogAsset::class,
        'title' => 'كتالوج أصول البيانات',
        'description' => 'سجل أصول البيانات ومالكيها ودرجة حساسيتها.',
        'columns' => ['asset_key' => 'المعرف الفني', 'name_ar' => 'الاسم', 'asset_type' => 'النوع', 'domain' => 'المجال', 'system' => 'النظام', 'sensitivity' => 'الحساسية'],
        'fields' => [
            ['key' => 'asset_key', 'label' => 'المعرف الفني', 'required' => true],
            ['key' => 'name_ar', 'label' => 'الاسم العربي'],
            ['key' => 'name_en', 'label' => 'الاسم الإنجليزي', 'required' => true],
            ['key' => 'asset_type', 'label' => 'نوع الأصل', 'type' => 'select', 'options' => ['Operational Entity', 'Master Data', 'Analytics Feed', 'Lakehouse', 'Warehouse', 'Semantic Model', 'Report', 'GIS Layer', 'AI Service', 'External Standard', 'Other'], 'required' => true],
            ['key' => 'domain', 'label' => 'مجال البيانات', 'type' => 'select', 'options' => $domainOptions, 'required' => true],
            ['key' => 'system', 'label' => 'النظام المصدر', 'type' => 'select', 'options' => ['HAWAT', 'Microsoft Fabric', 'Power BI', 'ArcGIS', 'FAO', 'External'], 'required' => true],
            ['key' => 'source_of_truth', 'label' => 'مصدر الحقيقة', 'type' => 'boolean'],
            ['key' => 'owner_email', 'label' => 'مالك البيانات'],
            ['key' => 'steward_email', 'label' => 'مسؤول البيانات'],
            ['key' => 'sensitivity', 'label' => 'الحساسية', 'type' => 'select', 'options' => ['عام', 'داخلي', 'مقيد', 'شخصي', 'حساس']],
            ['key' => 'contains_pii', 'label' => 'يحتوي بيانات شخصية', 'type' => 'boolean'],
            ['key' => 'refresh_mode', 'label' => 'التحديث', 'type' => 'select', 'options' => $refreshOptions],
            ['key' => 'retention_note', 'label' => 'سياسة الاحتفاظ'],
            ['key' => 'purpose', 'label' => 'الغرض', 'type' => 'textarea'],
            ['key' => 'quality_controlled', 'label' => 'خاضع لفحص الجودة', 'type' => 'boolean'],
            ['key' => 'active', 'label' => 'نشط', 'type' => 'boolean'],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'lineage-edges' => [
        'label' => 'مسارات البيانات',
        'model' => DataLineageEdge::class,
        'title' => 'مسارات البيانات',
        'description' => 'العلاقات بين أصول البيانات من المصدر إلى الاستخدام.',
        'columns' => ['source_asset' => 'من', 'target_asset' => 'إلى', 'transform' => 'التحويل', 'refresh_mode' => 'التحديث'],
        'fields' => [
            ['key' => 'source_asset', 'label' => 'الأصل المصدر', 'type' => 'select', 'options_from' => ['model' => DataCatalogAsset::class, 'column' => 'asset_key'], 'required' => true],
            ['key' => 'target_asset', 'label' => 'الأصل الهدف', 'type' => 'select', 'options_from' => ['model' => DataCatalogAsset::class, 'column' => 'asset_key'], 'required' => true],
            ['key' => 'transform', 'label' => 'نوع التحويل'],
            ['key' => 'refresh_mode', 'label' => 'التحديث', 'type' => 'select', 'options' => $refreshOptions],
            ['key' => 'active', 'label' => 'نشط', 'type' => 'boolean'],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'glossary-terms' => [
        'label' => 'مصطلحات الأعمال',
        'model' => BusinessGlossaryTerm::class,
        'title' => 'قاموس الأعمال',
        'description' => 'التعريفات المعتمدة للمصطلحات المستخدمة في التقارير.',
        'columns' => ['term_ar' => 'المصطلح', 'term_en' => 'English', 'domain' => 'المجال', 'owner_email' => 'المالك', 'status' => 'الحالة'],
        'badges' => ['status' => ['معتمد' => 'ok', 'مسودة' => 'warn', 'بحاجة مراجعة' => 'warn']],
        'fields' => [
            ['key' => 'term_ar', 'label' => 'المصطلح العربي', 'required' => true],
            ['key' => 'term_en', 'label' => 'المصطلح الإنجليزي'],
            ['key' => 'domain', 'label' => 'المجال', 'type' => 'select', 'options' => $domainOptions],
            ['key' => 'definition', 'label' => 'التعريف العربي', 'type' => 'textarea', 'required' => true],
            ['key' => 'definition_en', 'label' => 'التعريف الإنجليزي', 'type' => 'textarea'],
            ['key' => 'owner_email', 'label' => 'المالك'],
            ['key' => 'related_entities', 'label' => 'الكيانات المرتبطة'],
            ['key' => 'status', 'label' => 'الحالة', 'type' => 'select', 'options' => ['معتمد', 'مسودة', 'بحاجة مراجعة']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'kpi-registry' => [
        'label' => 'سجل المؤشرات',
        'model' => KpiRegistry::class,
        'title' => 'سجل المؤشرات',
        'description' => 'تعريف المؤشرات ومعادلاتها ومصادرها وحالة اعتمادها.',
        'columns' => ['kpi_key' => 'المعرف', 'name_ar' => 'المؤشر', 'unit' => 'الوحدة', 'domain' => 'المجال', 'refresh_mode' => 'التحديث', 'status' => 'الاعتماد'],
        'badges' => ['status' => ['معتمد' => 'ok', 'مسودة' => 'warn', 'مرفوض' => 'danger']],
        'fields' => [
            ['key' => 'kpi_key', 'label' => 'معرّف المؤشر', 'required' => true],
            ['key' => 'name_ar', 'label' => 'اسم المؤشر', 'required' => true],
            ['key' => 'name_en', 'label' => 'الاسم الإنجليزي'],
            ['key' => 'domain', 'label' => 'المجال', 'type' => 'select', 'options' => ['Production', 'Sustainability', 'Compliance', 'Markets', 'Fleet', 'Other']],
            ['key' => 'unit', 'label' => 'وحدة القياس', 'type' => 'select', 'options' => ['كجم', 'طن', 'رحلة', 'قارب', 'نسبة %', 'ريال', 'عدد']],
            ['key' => 'formula', 'label' => 'المعادلة', 'type' => 'textarea'],
            ['key' => 'source_entities', 'label' => 'الكيانات المصدر'],
            ['key' => 'owner', 'label' => 'المالك'],
            ['key' => 'refresh_mode', 'label' => 'التحديث', 'type' => 'select', 'options' => $refreshOptions],
            ['key' => 'status', 'label' => 'حالة الاعتماد', 'type' => 'select', 'options' => ['مسودة', 'معتمد', 'مرفوض']],
            ['key' => 'notes', 'label' => 'ملاحظات', 'type' => 'textarea'],
        ],
    ],

    'audit-logs' => [
        'label' => 'سجل العمليات',
        'model' => AuditLog::class,
        'title' => 'سجل العمليات',
        'description' => 'سجل غير قابل للتعديل لكل العمليات الحساسة في النظام.',
        'readonly' => true,
        'columns' => ['created_at' => 'الوقت', 'action' => 'العملية', 'entity' => 'الكيان', 'record_label' => 'السجل', 'user_email' => 'المستخدم', 'role' => 'الدور', 'details' => 'التفاصيل'],
        'fields' => [],
    ],

];
