<style>
/*
 * بوابة المعلومات — ما تنفرد به وحدها.
 *
 * نظام التصميم كلّه في partials/styles.blade.php ويُحمَّل قبل هذا الملف:
 * المتغيّرات والوضعان الفاتح والداكن، والشريط العلوي والقائمة الجانبية،
 * والبطاقة والجدول والحقل والزرّ. فلا يُعاد هنا شيء منها — إنما التبويبات
 * والنموذج المنبثق وما تحتاجه بنية هذه البوابة وحدها.
 */

/*
 * القائمة أوسع قليلًا من قائمة اللوحة: أسماء تبويبات البوابة أطول — "كتالوج
 * ومسارات البيانات"، "قاموس الأعمال والمؤشرات" — فكانت تُقصّ بثلاث نقاط عند
 * 12.5rem. والشريط العلوي يتبعها لأن كتلة الشعار بعرضها.
 */
:root { --sidebar-w: 15rem; }

/*
 * أيقونات البوابة تُرسم من مسارات في admin/partials/icon.blade.php بلا سمات
 * قياس، فتأخذ قياسها من هنا. وأي قاعدة أخصّ منها (.btn svg، .nav-link svg)
 * تتقدّم عليها كما هي في اللوحة.
 */
.icon { width: 1rem; height: 1rem; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

/* رابط الشريط العلوي: أبيض على الأزرق كأزراره، والنصّ يسقط على الشاشة الضيقة. */
.topbar-link { display: inline-flex; align-items: center; gap: .35rem; padding: .5rem .8rem; font-size: .78rem; font-weight: 600; color: hsl(0 0% 100% / .82); }
.topbar-link:hover { color: var(--topbar-fg); }
.topbar-link svg { width: 16px; height: 16px; }
.topbar-actions form { display: flex; }
@media (max-width: 640px) { .topbar-link span { display: none; } }

/* رسالة الخطأ: نسخة حمراء من .flash الخضراء التي في اللوحة. */
.flash-error { border-color: hsl(352 80% 50% / .45); background: hsl(352 80% 50% / .1); color: #be123c; }
html.dark .flash-error { color: hsl(352 85% 72%); }
.flash-error ul { padding-inline-start: 1.1rem; }
.flash-error li { line-height: 1.9; }

/*
 * تنبيه البوابة: صندوق كهرماني يقول ما لا يُحرَّر من هنا. مبنيّ على قاعدة
 * .note-box نفسها لكن بلون التحذير لا بلون التأكيد.
 */
.notice { display: flex; align-items: flex-start; gap: .7rem; border: 1px solid hsl(38 90% 45% / .45); background: hsl(38 90% 45% / .08); padding: .8rem .95rem; font-size: .78rem; line-height: 1.9; }
.notice svg { width: 18px; height: 18px; margin-top: .15rem; color: #b45309; }
html.dark .notice svg { color: hsl(38 90% 66%); }
.notice strong { font-weight: 700; }

/*
 * شريط التبويبات: القائمة الجانبية تعرضها كلّها، وهذا الشريط طريق أقصر إليها
 * من داخل الصفحة. رقاقات بحدٍّ شعري وزاوية قائمة، والنشطة وحدها ممتلئة باللون.
 */
.tabbar { display: flex; flex-wrap: wrap; gap: .3rem; }
.tabbar-item { display: inline-flex; align-items: center; gap: .4rem; border: 1px solid hsl(var(--border)); padding: .35rem .7rem; font-size: .74rem; font-weight: 600; color: hsl(var(--muted-foreground)); transition: color .15s, border-color .15s, background .15s; }
.tabbar-item svg { width: 14px; height: 14px; }
.tabbar-item:hover { color: hsl(var(--foreground)); border-color: hsl(var(--primary) / .5); }
.tabbar-item.is-active { background: hsl(var(--primary)); border-color: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }

/* لوحة التبويب: بطاقة اللوحة نفسها (.card) بفاصل أوسع بين كتلها. */
.panel { gap: 1rem; padding: 1.1rem 1.15rem; }

/* تبويبات الموارد داخل اللوحة — أصغر من شريط التبويبات وبلا أيقونات. */
.subtabbar { display: flex; flex-wrap: wrap; gap: .3rem; }
.subtab { border: 1px solid hsl(var(--border)); padding: .3rem .75rem; font-size: .74rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.subtab:hover { color: hsl(var(--foreground)); border-color: hsl(var(--primary) / .5); }
.subtab.is-active { background: hsl(var(--primary)); border-color: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }

/*
 * ترويسة القسم هنا سطران وزرّ في الطرف المقابل — لا العنوان والخطّ الممتدّ
 * الذي في لوحات الوزارة، فتُعاد صياغتها على هذا الشكل.
 */
.section-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem; margin: 0; }
.section-head h2 { font-size: .95rem; font-weight: 700; letter-spacing: 0; white-space: normal; }
.section-head p { margin-top: .15rem; font-size: .72rem; line-height: 1.75; color: hsl(var(--muted-foreground)); }

.empty-state { padding: 2.5rem 1.25rem; text-align: center; font-size: .8rem; color: hsl(var(--muted-foreground)); }

/* خلايا الإجراءات: زرّان متجاوران في وسط الخانة، بلا لفّ سطر بينهما. */
.cell-actions { text-align: center; white-space: nowrap; }
.inline-form { display: inline; }
.cell-actions .icon-action { display: inline-grid; place-items: center; vertical-align: middle; margin-inline-start: .15rem; }

/* رقاقة محايدة — لِما لا حالة له: "معطل"، "بُعد"، "فارغ". */
.badge-muted { color: hsl(var(--muted-foreground)); border-color: hsl(var(--border)); background: hsl(var(--muted) / .6); }

.pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; font-size: .74rem; color: hsl(var(--muted-foreground)); }
.pager nav { display: flex; gap: .3rem; }
.pager nav a, .pager nav span { border: 1px solid hsl(var(--border)); padding: .25rem .7rem; }
.pager nav a:hover { border-color: hsl(var(--primary) / .6); color: hsl(var(--foreground)); }

/*
 * الحقل: التسمية عنصر <label> لا <span>، فتأخذ ما تأخذه أختها في اللوحة.
 * والحقول نفسها تحمل .input و .select فتُصمَّم من هناك.
 */
.field label { font-size: .7rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.field .req { color: #d61f47; margin-inline-start: .15rem; }
html.dark .field .req { color: hsl(352 85% 72%); }
.field textarea.input { min-height: 5rem; resize: vertical; line-height: 1.8; }
/* خانة الاختيار سطر واحد: المربّع ثم نصّه. */
.field-check { flex-direction: row; align-items: center; gap: .5rem; }
.field-check input[type=checkbox] { width: 1rem; height: 1rem; accent-color: hsl(var(--primary)); cursor: pointer; }
.field-check label { font-size: .78rem; font-weight: 600; color: hsl(var(--foreground)); cursor: pointer; }
.field-wide { grid-column: 1 / -1; }

/*
 * النموذج المنبثق: <dialog> أصليّ لا لوح منزلق، فيبقى بسطحٍ مصمت — هو فوق
 * الصفحة لا فيها — لكن بزاويته القائمة وخطّه الشعري كبقية اللوحة.
 */
dialog.modal {
    border: 1px solid hsl(var(--border)); border-radius: 0; padding: 0;
    /* التوسيط من المتصفّح نفسه، و* { margin: 0 } في اللوحة يُبطله فيُعاد هنا. */
    margin: auto;
    width: min(70rem, 96vw); max-width: 96vw; max-height: 94vh;
    background: hsl(var(--background)); color: hsl(var(--foreground));
    font-family: inherit;
    box-shadow: 0 28px 70px -20px rgba(0, 0, 0, .55);
}
dialog.modal::backdrop { background: rgba(0, 0, 0, .6); }
.modal-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--hair); padding: .9rem 1.2rem; }
.modal-head h3 { font-size: 1rem; font-weight: 700; }
.modal-body { padding: 1.2rem; }
.modal-foot { display: flex; gap: .5rem; border-top: 1px solid var(--hair); padding: .85rem 1.2rem; }
/*
 * بلا تمرير داخلي: النموذج يتمدّد ليتسع لحقوله كلّها دفعة واحدة، والحقول
 * تتراصّ بعرضٍ أدنى ثابت فيملأ الصفّ ما وسعه منها.
 */
.modal .form-grid { grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); gap: .75rem .85rem; }
.modal .field textarea.input { min-height: 4rem; }
</style>
