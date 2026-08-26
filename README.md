# حوات إحصاء — منصّة المصايد البحرية (Laravel)

تطبيق Laravel واحد يقدّم بوابتين على مضيفين مختلفين فوق نفس قاعدة البيانات:

| المضيف | البوابة | الوصف |
|---|---|---|
| `hawat.sa` | **لوحة الوزارة** | لوحة عرض وتحليل: المؤشرات الوطنية، الخريطة البحرية، الإنتاج، الموانئ، الاستدامة، الأسواق والأمن الغذائي. |
| `info.hawat.sa` | **بوابة المعلومات — مركز إدارة النظام** | تحرير البيانات الأساسية (Master Data) عبر عشرين تبويبًا، مع سجل عمليات غير قابل للتعديل. |

محليًا: `ihsa.test` و`info.ihsa.test`.

ولوحة الوزارة نفسها مقسومة إلى **خمس بوابات** على النطاق الرئيسي، تُختار من صفحة `/`
ويحدّدها `App\Support\Nav` من اسم المسار — والتبويب الواحد يظهر في قائمة بوابته وحدها:

| البادئة | البوابة | القائمة |
|---|---|---|
| `/gov` | لوحة الحكومة | `hawat.nav_gov` |
| `/stats` | قسم الإحصاء | `hawat.nav_stats` |
| `/subadmin` | قسم الإدارة الفرعية | `hawat.nav_subadmin` |
| `/services` | قسم الخدمات والتراخيص | `hawat.nav_services` |
| `/admin` | المنصة التشغيلية | `hawat.nav` |

نقل لوحة بين البوابات يستلزم `Route::permanentRedirect` من موضعها القديم — يحرس ذلك
`tests/Feature/PortalSplitTest.php`.

---

## 1) المتطلبات

- PHP ≥ 8.2 مع الإضافات: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Composer ≥ 2
- MySQL 8 / MariaDB 10.6

لا حاجة إلى Node.js أو خطوة build — نظام التصميم مضمّن في Blade.

---

## 2) التشغيل

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

ثم اضبط المضيفين في `.env`:

```dotenv
APP_URL=http://ihsa.test          # مضيف لوحة الوزارة
INFO_PORTAL_DOMAIN=info.ihsa.test # مضيف بوابة المعلومات
SESSION_DOMAIN=.ihsa.test         # النطاق الأب المشترك بين المضيفين
```

وأضف المضيفين إلى `hosts` (يفعل ذلك Herd تلقائيًا لمجلد المشروع ونطاقاته الفرعية).

### تشغيل البوابة بلا مضيف مستقل

اترك `INFO_PORTAL_DOMAIN` فارغًا فتُقدَّم بوابة المعلومات تحت البادئة `/info`
على نفس مضيف اللوحة، وتصبح تبويباتها على `/info/admin/{tab}`. لا يتغيّر شيء آخر.

---

## 3) فصل المضيفين

`routes/web.php` يسجّل بوابة المعلومات **قبل** لوحة الوزارة: المسار بلا قيد نطاق
يلتقط أي مضيف، فلو جاءت اللوحة أولًا لابتلعت `/` على `info.hawat.sa`.

ولوحة الوزارة بدورها مقيَّدة بـ `config('hawat.domain')` (يُشتق من `APP_URL`) كي
لا تظهر صفحاتها أيضًا على مضيف البوابة. حين تُترك `INFO_PORTAL_DOMAIN` فارغة
يسقط هذا القيد تلقائيًا.

يحرس `tests/Feature/InfoPortalTest.php` هذا الفصل في الاتجاهين.

---

## 4) معمارية بوابة المعلومات

مبنية على نمط **Config-Driven Resources**: تعريف واحد لكل مورد في ملف إعدادات،
وطبقة عرض عامة تتولّى الجدول والنموذج والتحقق. إضافة جدول جديد لا تحتاج
Controller ولا View جديدة.

```
config/info.php                تعريف التبويبات العشرين + المضيف + إعدادات التكاملات
config/info_resources.php      تعريف كل مورد: النموذج، الأعمدة، الحقول، الشارات
app/Support/AdminRegistry.php  حلّ التبويبات/الموارد، بناء الحقول، توليد قواعد التحقق
app/Support/ToolData.php       بيانات تبويبات الأدوات (الإحصاءات، الاستيراد، Power BI)
app/Http/Controllers/
  AdminController.php               عرض التبويب وتحديد نوع اللوحة (مورد/تكامل/أداة)
  AdminResourceController.php       إنشاء/تعديل/حذف مع تسجيل تلقائي في سجل العمليات
  IntegrationSettingController.php  حفظ إعدادات التكاملات
```

### الحقول المرتبطة بمفاتيح أجنبية

المخطط علائقي (`region_id` / `governorate_id` / `port_id` / `boat_id` …)، لذا
تدعم `options_from` شكلين:

```php
// قائمة قيم نصية — القيمة هي التسمية
['key' => 'top_port', 'type' => 'select',
 'options_from' => ['model' => Port::class, 'column' => 'name']],

// مفتاح أجنبي — تُخزَّن id وتُعرض التسمية
['key' => 'port_id', 'type' => 'select',
 'options_from' => ['model' => Port::class, 'value' => 'id', 'label' => 'name']],
```

وتُعرض في الجدول بمسار نقطي مع تحميل مسبق تفاديًا لاستعلامات N+1:

```php
'with' => ['governorate.region'],
'columns' => ['name' => 'الاسم', 'governorate.region.name' => 'المنطقة'],
```

### إضافة مورد جديد

1. أضف الجدول في Migration والموديل في `app/Models` (بالوراثة من `BaseModel`).
2. أضف تعريفه في `config/info_resources.php` (columns / fields / badges / with).
3. أضف مفتاحه في مصفوفة `resources` للتبويب المناسب في `config/info.php`.

يظهر تلقائيًا بجدول قابل للترقيم ونموذج إضافة/تعديل مع تحقق كامل.

---

## 5) قاعدة البيانات

| Migration | الجداول |
|---|---|
| `create_geographic_tables` | regions, governorates, ports, fishing_sites |
| `create_fleet_tables` | species, gear_types, boats, fishers, statistics_officers |
| `create_seasons_tables` | fishing_seasons, season_licenses |
| `create_operations_tables` | trips, catch_records, bycatch_records, alerts, violations |
| `create_market_tables` | markets, market_auctions |
| `create_governance_tables` | data_catalog_assets, data_lineage_edges, business_glossary_terms, kpi_registries, data_quality_issues, fao_standard_mappings |
| `create_system_tables` | user_permissions, audit_logs, ui_translations, integration_settings |
| `extend_tables_for_info_portal` | الحقول المرجعية الإضافية التي تحرّرها البوابة ولا تحتاجها اللوحة |
| `create_sub_administration_tables` | org_positions, org_staff, admin_tasks, staff_notifications, notification_settings |
| `create_services_licensing_tables` | fisher_service_types, fisher_service_staff, fisher_service_staff_type, fisher_service_requests, support_tickets |

جميع الـ Seeders تستخدم `updateOrCreate` — تشغيلها أكثر من مرة آمن ولا يُكرّر البيانات.

---

## 6) نظام التصميم

- الخط: **Tajawal** (400/500/700/800) من Google Fonts، والاتجاه RTL على مستوى الصفحة.
- توكنز البوابة في `resources/views/admin/partials/styles.blade.php`، وتوكنز اللوحة
  في `resources/views/partials/styles.blade.php`.
- الجداول: ترويسة رمادية خفيفة، تباين صفوف بالتناوب، شارات حالة بنقطة لونية
  (`badge-ok` / `badge-warn` / `badge-danger`)، أزرار تعديل/حذف أيقونية.
- الأيقونات SVG داخلية — بدون أي اعتماد خارجي.

---

## 7) الأمان

- عمليات الكتابة تمر عبر `AdminResourceController` مع تحقق مُولّد من تعريف الحقول
  (`required` / `numeric` / `date` / `in:` للقوائم، بما فيها قيم المفاتيح الأجنبية).
- تبويب سجل العمليات معرّف `readonly` — أي محاولة كتابة عليه تُرفض بـ 403.
- كل إنشاء/تعديل/حذف يُسجّل تلقائيًا في `audit_logs` مع المستخدم والدور و IP.
- الحذف محمي بتأكيد، وكل النماذج تحمل `@csrf`.

> بوابة المعلومات **غير محمية بمصادقة بعد**. لتقييد الوصول أضف
> `->middleware(['auth'])` على مجموعة مسارات البوابة في `routes/web.php`.

---

## 8) الاختبارات

```bash
php artisan test
```

تعمل على SQLite في الذاكرة، وتضبط المضيفين عبر `phpunit.xml`
(`hawat.test` / `info.hawat.test`) حتى لا تعتمد على `.env` المحلي.

---

## 9) النشر

الدفع إلى `main` يشغّل `.github/workflows/deploy.yml` الذي ينفّذ على الخادم:

```bash
cd /www/wwwroot/ihsa
git pull --ff-only origin main
php artisan migrate --force
php artisan optimize
```

المضيفان `hawat.sa` و`info.hawat.sa` يشيران إلى نفس الجذر `/www/wwwroot/ihsa/public`،
والفصل بينهما يتم داخل Laravel لا في إعدادات الخادم.
