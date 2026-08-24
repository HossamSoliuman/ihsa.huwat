{{--
    أنماط النشرة السنوية — مشتركة بين العرض داخل اللوحة ونسخة الطباعة.

    كل الرسوم هنا مبنية بـ CSS لا بـ canvas: النشرة مستند يُطبع، ونافذة الطباعة
    قد تُستدعى قبل أن ترسم مكتبة رسوم لوحاتها فتخرج الصفحة فارغة.
--}}
<style>
.bulletin { max-width: 920px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem; }
.bp {
    position: relative; overflow: hidden; box-sizing: border-box;
    border: 1px solid rgba(148,163,184,.26); border-radius: 28px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    padding: 34px 38px 46px; color: #0f172a;
    box-shadow: 0 24px 70px rgba(15,23,42,.09);
}
.bp::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 5px; background: linear-gradient(90deg,#063f78,#0b78a6,#16a3a5); }
.bp-head { display: flex; align-items: center; justify-content: space-between; gap: 18px; border-bottom: 1px solid rgba(148,163,184,.22); padding-bottom: 15px; margin-bottom: 22px; }
.bp-kicker { font-size: 8px; font-weight: 800; letter-spacing: 1.8px; color: #0891b2; }
.bp-head h2 { margin-top: 4px; font-size: 23px; font-weight: 800; color: #083c72; }
.bp-num { display: inline-flex; min-width: 42px; height: 32px; align-items: center; justify-content: center; border-radius: 11px; background: linear-gradient(135deg,#063f78,#0b78a6); color: #fff; font-size: 11px; font-weight: 800; }
.bp-foot { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(148,163,184,.2); margin-top: 26px; padding-top: 8px; font-size: 8.5px; color: #94a3b8; }
.bp-foot b { color: #0b78a6; }

.bp-cover { color: #fff; padding: 52px; border: 1px solid rgba(255,255,255,.12); text-align: center;
    background: radial-gradient(circle at 18% 18%, rgba(14,165,233,.26), transparent 30%),
                radial-gradient(circle at 82% 72%, rgba(20,184,166,.18), transparent 32%),
                linear-gradient(145deg,#031d38 0%,#073f72 48%,#086b91 100%); }
.bp-cover::before { height: 0; }
.hawat-mark { display: inline-block; border-radius: 14px; background: linear-gradient(90deg,#0b7fd3,#15b8c7); padding: 7px 20px; color: #fff; font-size: 31px; font-weight: 800; letter-spacing: -2px; }
.bp-cover .eyebrow { display: inline-block; margin: 34px 0 20px; border: 1px solid rgba(255,255,255,.18); border-radius: 999px; background: rgba(255,255,255,.07); padding: 7px 13px; font-size: 8px; font-weight: 800; letter-spacing: 1.8px; color: #bce8ff; }
.bp-cover h1 { max-width: 680px; margin: 0 auto; font-size: 40px; font-weight: 800; line-height: 1.35; }
.bp-cover .lede { margin-top: 10px; font-size: 17px; color: #d8efff; }
.cover-year { display: inline-flex; align-items: center; gap: 12px; margin-top: 28px; border: 1px solid rgba(255,255,255,.16); border-radius: 18px; background: rgba(0,0,0,.09); padding: 8px 15px 8px 10px; }
.cover-year span { font-size: 10px; color: #b7def2; }
.cover-year strong { display: inline-flex; min-width: 88px; justify-content: center; border-radius: 13px; background: #fff; padding: 6px 12px; color: #075a85; font-size: 25px; font-weight: 800; }
.cover-bottom { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; border-top: 1px solid rgba(255,255,255,.18); margin-top: 40px; padding-top: 20px; }
.cover-bottom div { border-radius: 14px; background: rgba(255,255,255,.06); padding: 10px 6px; font-size: 11px; }
.cover-note { margin-top: 15px; font-size: 9px; color: #ccecff; }

.b-grid { display: grid; gap: 12px; }
.b-2 { grid-template-columns: repeat(2, 1fr); }
.b-3 { grid-template-columns: repeat(3, 1fr); }
.b-4 { grid-template-columns: repeat(4, 1fr); }
.b-5 { grid-template-columns: repeat(5, 1fr); }
.b-split { grid-template-columns: 1.2fr .8fr; gap: 20px; }

.tile { border: 1px solid rgba(148,163,184,.2); border-radius: 16px; background: linear-gradient(180deg,#fff,#fbfdff); padding: 13px; }
.tile .l { font-size: 9px; font-weight: 700; color: #64748b; }
.tile .v { margin-top: 3px; font-size: 16px; font-weight: 800; color: #083c72; }
.tile .s { margin-top: 2px; font-size: 9px; color: #94a3b8; }
.summary { border: 1px solid rgba(14,116,144,.13); border-radius: 20px; background: linear-gradient(145deg,#fff,#f8fcff); padding: 17px; }
.summary .l { margin-top: 13px; font-size: 10px; font-weight: 700; color: #64748b; }
.summary .v { margin-top: 4px; font-size: 20px; font-weight: 800; color: #0f3056; }
.callout { border: 1px solid; border-radius: 18px; padding: 18px; }
.callout .l { font-size: 11px; font-weight: 700; }
.callout .v { margin-top: 8px; font-size: 22px; font-weight: 800; }
.callout.green { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
.callout.red { border-color: #fecdd3; background: #fff1f2; color: #9f1239; }
.callout.blue { border-color: #bae6fd; background: #f0f9ff; color: #075985; }

.panel { border: 1px solid rgba(148,163,184,.2); border-radius: 20px; background: #fff; padding: 16px; }
.panel-head { display: flex; align-items: center; gap: 8px; margin-bottom: 13px; }
.panel-dot { width: 8px; height: 8px; border-radius: 999px; background: linear-gradient(135deg,#0b78a6,#14b8a6); box-shadow: 0 0 0 5px rgba(14,165,164,.08); }
.panel-head h3 { font-size: 11px; font-weight: 800; color: #0f416f; }

/* أعمدة أفقية بعرض نسبي: بديل الرسم البياني الذي يُطبع بثبات. */
.bars { display: flex; flex-direction: column; gap: 7px; }
.bar-row { display: grid; grid-template-columns: 92px 1fr 68px; align-items: center; gap: 8px; font-size: 9.5px; }
.bar-row .name { color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
/* العنصران span داخل شبكة: بدون display:block يبقيان سطريين فيسقط العرض والارتفاع. */
.bar-track { display: block; height: 11px; border-radius: 999px; background: #eef2f7; overflow: hidden; }
.bar-fill { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg,#0759b5,#0787a6); }
.bar-row .val { text-align: left; font-weight: 800; color: #0f3056; }
.columns { display: flex; align-items: flex-end; gap: 5px; height: 150px; padding-top: 10px; }
.columns .col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 4px; }
.columns .col .bar { width: 100%; min-height: 2px; border-radius: 5px 5px 0 0; background: linear-gradient(180deg,#0787a6,#0759b5); }
.columns .col .bar.last { background: linear-gradient(180deg,#4ade80,#38a169); }
.columns .col .lbl { font-size: 8px; color: #64748b; white-space: nowrap; }

/* توزيع نسبي على شريط واحد بدل قرص دائري. */
.share { display: flex; height: 16px; overflow: hidden; border-radius: 999px; background: #eef2f7; }
.share span { height: 100%; }
.legend { display: flex; flex-direction: column; gap: 4px; margin-top: 10px; font-size: 9.5px; }
.legend .row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.legend .row i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-inline-end: 6px; }
.legend .row b { color: #0f3056; }

/* خريطة الكثافة: النقاط موضوعة بالنسبة إلى نطاق إحداثيات المملكة. */
.map-box { position: relative; height: 420px; border: 1px solid #dbeafe; border-radius: 18px; background: linear-gradient(180deg,#f0f9ff,#fff); background-image: linear-gradient(#e0f2fe 1px, transparent 1px), linear-gradient(90deg, #e0f2fe 1px, transparent 1px); background-size: 10% 10%; }
/* النقطة موضوعة بحافتيها اليمنى والسفلى، فتُزاح بنصف قياسها لتتمركز على الإحداثي. */
.map-box .pt { position: absolute; transform: translate(50%, 50%); border-radius: 999px; background: rgba(7,89,181,.5); box-shadow: 0 0 0 1px rgba(7,89,181,.65); }
.map-box .edge { position: absolute; top: 10px; font-size: 9px; font-weight: 600; color: #64748b; }
.map-note { margin-top: 8px; text-align: center; font-size: 8.5px; color: #94a3b8; }

.b-table { width: 100%; border-collapse: collapse; font-size: 10px; }
.b-table thead { background: linear-gradient(90deg,#eef7ff,#effaf8); color: #0b4f78; }
.b-table th { border-bottom: 1px solid rgba(14,116,144,.12); padding: 9px 10px; text-align: right; font-weight: 800; white-space: nowrap; }
.b-table td { border-bottom: 1px solid rgba(226,232,240,.72); padding: 8px 10px; text-align: right; color: #475569; }
.b-table tbody tr:nth-child(even) { background: #fbfdff; }
.b-table .empty { padding: 40px 16px; text-align: center; color: #94a3b8; }
.b-table-wrap { overflow: hidden; border: 1px solid rgba(148,163,184,.22); border-radius: 18px; background: #fff; }

.appendix { border: 1px solid rgba(148,163,184,.2); border-radius: 20px; background: linear-gradient(160deg,#fff,#f8fcff); padding: 20px; }
.appendix h3 { font-size: 13px; font-weight: 800; color: #0a416f; }
.appendix p { margin-top: 7px; font-size: 10.5px; line-height: 1.9; color: #64748b; }
.b-note { border: 1px solid #bae6fd; border-radius: 18px; background: #f0f9ff; padding: 18px; font-size: 11px; line-height: 1.9; color: #075985; }
.b-warn { display: flex; gap: 12px; border: 1px solid #fde68a; border-radius: 18px; background: #fffbeb; padding: 16px; font-size: 11px; line-height: 1.8; color: #78350f; }
.b-prose { font-size: 13px; line-height: 2; color: #334155; white-space: pre-line; }
.b-sign { margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 12px; font-size: 12px; font-weight: 700; color: #1d4ed8; }

@media (max-width: 900px) {
    .bp { border-radius: 20px; padding: 22px; }
    .bp-cover { padding: 30px; }
    .bp-cover h1 { font-size: 28px; }
    .b-3, .b-4, .b-5, .b-split, .cover-bottom { grid-template-columns: repeat(2, 1fr); }
}

@media print {
    .bulletin { max-width: none; gap: 0; }
    .bp {
        width: 210mm; height: 297mm; border: 0; border-radius: 0; box-shadow: none;
        margin: 0; padding: 13mm; page-break-after: always; break-after: page;
    }
    .bp:last-child { page-break-after: auto; break-after: auto; }
    @page { size: A4 portrait; margin: 0; }
}
</style>
