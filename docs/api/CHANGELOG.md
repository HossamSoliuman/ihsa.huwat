# سجل تغييرات واجهة التطبيق — `/api/v1`

المواصفة الكاملة: `docs/api/openapi.yaml` (تُعرض على `/api/docs`، وتُستورد في Postman من `/api/openapi.yaml`).
كل ردّ في الاختبارات يُطابَق مع المواصفة (Spectator)، فما هنا هو ما يعمل فعلًا.

## 1.1.0 — 2026-09-20 — المرحلة 1: بوابة المالك

### جديد
- `GET /lookups` — كل القوائم المرجعية في ردّ واحد (الأصناف، الموانئ، أدوات الصيد، تصنيفات/أنواع القوارب، أنواع الصيانة، أنواع التصريح، أدوار الطاقم، أنواع الهوية، المسميات الوظيفية، طرق الدفع، حالات الدفع، أنواع العملاء).
- `GET /owner/dashboard` — المؤشرات، إيرادات ستة أشهر، الأسماك المتوفرة، الرحلات النشطة، آخر المبيعات.
- `GET /owner/stock`، `GET /owner/stock/movements` — المخزون المتاح وحركات الصنف.
- `GET /owner/dalals` — الدلالون المفعّلون لقائمة الإرسال.
- **الأسطول**: `/owner/boats` (CRUD — الحذف يُرفض لقارب له رحلات)، `/owner/maintenance` (بلا show)، `/owner/captains` (index/store/show/update — الإنشاء يصنع حساب دخول + سجلّ صياد؛ التعطيل بـ `active=false`)، `/owner/crew` (بلا show).
- **الجهات**: `/owner/employees`، `/owner/customers`، `/owner/vendors` (CRUD).
- **الرحلات**: `/owner/trips` (index/store/show/update) + `POST /owner/trips/{trip}/start|cancel|catch` — نيابةً عن الكابتن. `?active=1` للرحلات النشطة، `?sale_status=قيد البيع` لما يحتاج تسجيل البيع. التفاصيل تحمل `catch_records` و`available_stock`.
- **البيع**: `POST /owner/sales` (لا يتجاوز المتاح من كل صنف؛ يخصم من دفتر المخزون؛ الرحلة تصير "مباعة" حين ينفد مصيدها)، `GET /owner/sales`, `GET /owner/sales/{sale}`.
- **الإرسال للدلال**: `POST /owner/consignments` + index/show.

### مفاهيم
- كل مسار تحت `/owner/*` يتطلب دور `owner` (وإلا `403`)، وكل سجل مقيّد بمالكه (`404` لغيره).
- حالات الرحلة (`status`) بمفردات الوزارة، ومعها `app_status` (التسمية في التطبيق) و`progress_step` (0–4) و`sale_status` (لم يبدأ / قيد البيع / مباعة) و`can_sell`.
- القوائم مصفّحة: `{ data, links, meta }` مع `?page=` و`?per_page=` (حتى 100).

### تغيّر
- `openapi` صارت `3.0.3` (كانت 3.1.0) لتوافق مولّدات العملاء وPostman؛ لا تغيير في مسارات `auth/*`.

## 1.0.0 — 2026-09-19 — المرحلة 0

- `POST /auth/login` (رمز لكل جهاز + `fcm_token`)، `POST /auth/logout`، `GET|PUT /auth/me`، `POST /auth/change-password`، `POST /auth/fcm-token`.
- استعادة كلمة المرور بالجوال: `POST /auth/forgot-password` → `POST /auth/verify-otp` → `POST /auth/reset-password`.
