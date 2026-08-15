# حوات — وحدة مركز إدارة النظام (Laravel)

نسخة Laravel كاملة من وحدة **مركز إدارة النظام — HAWAT Administration Center**، بنفس التصميم والخطوط والاتجاه RTL ونفس التبويبات العشرين، مع Migrations وSeeders وعمليات CRUD كاملة.

---

## 1) المتطلبات

- PHP ≥ 8.2 مع الإضافات: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Composer ≥ 2
- MySQL 8 / MariaDB 10.6 (أو SQLite للتجربة السريعة)

لا حاجة إلى Node.js أو خطوة build — نظام التصميم مضمّن في Blade.

---

## 2) التشغيل

```bash
composer create-project laravel/laravel hawat-admin
cd hawat-admin

# انسخ محتويات مجلد laravel/ فوق المشروع:
#   app/Models, app/Support, app/Http/Controllers
#   config/hawat.php, config/hawat_resources.php
#   database/migrations, database/seeders
#   resources/views, routes/web.php

cp .env.example .env
php artisan key:generate

# عدّل في .env:
#   APP_NAME="حوات"
#   APP_LOCALE=ar
#   APP_FALLBACK_LOCALE=ar
#   APP_TIMEZONE=Asia/Riyadh
#   DB_DATABASE=hawat

php artisan migrate --seed
php artisan serve
```

افتح `http://127.0.0.1:8000` → تُفتح وحدة مركز إدارة النظام على تبويب "البيانات الجغرافية".

للتجربة على SQLite:

```bash
touch database/database.sqlite
# في .env: DB_CONNECTION=sqlite ثم احذف بقية متغيرات DB_
php artisan migrate --seed
```

---

## 3) المعمارية

الوحدة مبنية على نمط **Config-Driven Resources**: تعريف واحد لكل مورد في ملف إعدادات، وطبقة عرض عامة تتولّى الجدول والنموذج والتحقق. إضافة جدول جديد لا تحتاج Controller ولا View جديدة.

```
config/hawat.php              تعريف التبويبات العشرين + إعدادات التكاملات
config/hawat_resources.php    تعريف كل مورد: النموذج، الأعمدة، الحقول، الشارات
app/Support/AdminRegistry.php  حلّ التبويبات/الموارد، بناء الحقول، توليد قواعد التحقق
app/Support/ToolData.php       بيانات تبويبات الأدوات (الإحصاءات، الاستيراد، Power BI)
app/Http/Controllers/
  AdminController.php          عرض التبويب وتحديد نوع اللوحة (مورد/تكامل/أداة)
  AdminResourceController.php  إنشاء/تعديل/حذف مع تسجيل تلقائي في سجل العمليات
  IntegrationSettingController.php  حفظ إعدادات التكاملات
routes/web.php                 مسارات admin/{tab} وعمليات الموارد
```

### إضافة مورد جديد

1. أضف الجدول في Migration والموديل في `app/Models` (بالوراثة من `MasterDataModel`).
2. أضف تعريفه في `config/hawat_resources.php` (columns / fields / badges).
3. أضف مفتاحه في مصفوفة `resources` للتبويب المناسب في `config/hawat.php`.

يظهر تلقائيًا بجدول قابل للفرز والترقيم ونموذج إضافة/تعديل مع تحقق كامل.

---

## 4) التبويبات (20)

| التبويب | النوع | المحتوى |
|---|---|---|
| البيانات الجغرافية | موارد | المناطق، المحافظات، الموانئ، مواقع الصيد |
| الأسطول والثروة السمكية | موارد | القوارب، الصيادون، الأنواع السمكية، أدوات الصيد، موظفو الإحصاء |
| مواسم الصيد | موارد | المواسم وفترات الحظر والحصص |
| الأسواق والمزادات | موارد | الأسواق، حركة المزادات والأسعار |
| الرخص | موارد | رخص المواسم والحصص المستخدمة |
| الصلاحيات | موارد | الأدوار والنطاق الجغرافي لكل مستخدم |
| الترجمة | موارد | كتالوج النصوص العربية ومقابلها الإنجليزي |
| الاستيراد الجماعي | أداة | جداول الاستيراد والأعمدة المطلوبة |
| إحصاءات الإنتاج | أداة | مؤشرات مجمّعة + جداول المناطق والموانئ |
| تكامل Power BI | تكامل | Workspace / Report / Dataset / نمط التضمين |
| مخطط Power BI | أداة | النموذج النجمي والمقاييس القياسية |
| بيانات Power BI | أداة | الجداول المتاحة للتغذية ودورية التحديث |
| تكامل ArcGIS | تكامل | طبقات الخرائط البحرية والخريطة الأساسية |
| تكامل Microsoft Fabric | تكامل | Lakehouse / Warehouse / SQL Endpoint |
| حوات AI | تكامل | النموذج، حدود السياق، وفرض النطاق الجغرافي |
| معايير وتقارير FAO | موارد | ربط ASFIS / ISSCAAP / ISSCFG وحالة التحقق |
| حوكمة وجودة البيانات | موارد | تذاكر الجودة والأولويات والاستحقاق |
| كتالوج ومسارات البيانات | موارد | أصول البيانات + مسارات الاعتماد بينها |
| قاموس الأعمال والمؤشرات | موارد | المصطلحات المعتمدة + سجل المؤشرات ومعادلاتها |
| سجل العمليات | موارد (عرض فقط) | سجل غير قابل للتعديل لكل العمليات |

---

## 5) قاعدة البيانات

خمسة Migrations مجمّعة حسب المجال، وتغطي 23 جدولًا:

| Migration | الجداول |
|---|---|
| `create_geographic_tables` | regions, governorates, ports, fishing_sites |
| `create_fleet_tables` | species, gear_types, boats, fishers, statistics_officers |
| `create_seasons_and_markets_tables` | fishing_seasons, season_licenses, markets, market_auctions |
| `create_governance_tables` | data_catalog_assets, data_lineage_edges, business_glossary_terms, kpi_registries, data_quality_issues, fao_standard_mappings |
| `create_system_tables` | user_permissions, audit_logs, ui_translations, integration_settings |

خمسة Seeders ببيانات واقعية: 4 مناطق، 8 محافظات، 8 موانئ، 6 مواقع صيد، 6 أنواع، 5 أدوات صيد، 5 قوارب، 5 صيادين، 4 موظفي إحصاء، 4 مواسم، 4 رخص، 4 أسواق، 5 مزادات، 8 أصول بيانات، 5 مسارات، 4 مصطلحات، 5 مؤشرات، 5 روابط FAO، 4 تذاكر جودة، 5 صلاحيات، 8 ترجمات، 4 تكاملات، و5 سجلات عمليات.

جميع الـ Seeders تستخدم `updateOrCreate` — تشغيلها أكثر من مرة آمن ولا يُكرّر البيانات.

---

## 6) نظام التصميم

مطابق لتوكنز التطبيق الأصلي:

- الخط: **Tajawal** (400/500/700/800) من Google Fonts، والاتجاه RTL على مستوى الصفحة.
- التوكنز في `resources/views/admin/partials/styles.blade.php` بنفس قيم HSL: `--primary: 199 89% 28%`، `--background: 210 40% 98%`، `--border: 214 25% 88%`، `--accent: 199 85% 90%`، `--radius: 0.5rem`.
- ترويسة الصفحة بأيقونة الدرع، صندوق تنبيه Master Data الكهرماني، شريط التبويبات بالتبويب النشط بلون primary، بطاقة لوحة بحدود ناعمة وظل خفيف.
- الجداول: ترويسة رمادية خفيفة، تباين صفوف بالتناوب، شارات حالة بنقطة لونية (`badge-ok` / `badge-warn` / `badge-danger`)، أزرار تعديل/حذف أيقونية.
- الأيقونات SVG داخلية (`partials/icon.blade.php`) بنفس أسلوب lucide المستخدم في التطبيق — بدون أي اعتماد خارجي.

---

## 7) الأمان

- عمليات الكتابة تمر عبر `AdminResourceController` مع تحقق مُولّد من تعريف الحقول (`required` / `numeric` / `date` / `in:` للقوائم).
- تبويب سجل العمليات معرّف `readonly` — أي محاولة كتابة عليه تُرفض بـ 403.
- كل إنشاء/تعديل/حذف يُسجّل تلقائيًا في `audit_logs` مع المستخدم والدور والتوقيت.
- الحذف محمي بتأكيد، وكل النماذج تحمل `@csrf`.

لتقييد الوصول للوحة بالمصادقة، أضف `->middleware(['auth'])` على مجموعة مسارات `admin` في `routes/web.php`.