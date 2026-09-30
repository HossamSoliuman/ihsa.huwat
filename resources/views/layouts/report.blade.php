<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    {{--
        ورقة تقارير المالك المطبوعة بنسق hispa (report-layout): شبكة جداول
        محدّدة برؤوس رمادية، وأشرطة أقسام سوداء، وبطاقات مؤشرات، وصندوق مجاميع،
        وتوقيعات. تُعرض على الشاشة ورقةَ A4 وتُطبع أو تُحفظ PDF من المتصفح.
    --}}
    <style>
        @page {
            size: A4 @yield('orientation');
            margin: 12mm 10mm 14mm;
            @bottom-center { content: "صفحة " counter(page) " من " counter(pages); font-family: 'Tajawal', sans-serif; font-size: 8pt; color: #888; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        a, a:link, a:visited { text-decoration: none; color: inherit; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { direction: rtl; font-family: 'Chakra Petch', 'Tajawal', sans-serif; color: #1a1a1a; background: #fff; font-size: 9pt; line-height: 1.5; }

        /* ترويسة: هوية المالك يمينًا وهوية المنصة يسارًا، ثم عنوان التقرير في الوسط. */
        .rmast { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding-bottom: 9px; margin-bottom: 6px; border-bottom: 1px solid #1a1a1a; }
        .rmast-side { flex: 1 1 0; min-width: 0; }
        .rmast-end { text-align: left; }
        .rmast-name { font-size: 13pt; font-weight: 800; line-height: 1.25; margin-bottom: 4px; }
        .rmast-facts { font-size: 8pt; line-height: 1.55; }
        .rmast-facts .rf { display: block; }
        .rmast-facts .rf-label { color: #888; font-weight: 500; }
        .rmast-facts .rf-label::after { content: ': '; }
        .rmast-facts .rf-value { font-weight: 700; }
        .rtitle-wrap { text-align: center; margin: 6px 0 18px; }
        .rtitle { font-size: 13pt; font-weight: 800; margin-bottom: 4px; }
        .rsubtitle { font-size: 10pt; color: #666; }

        .section-bar { background: #1a1a1a; color: #fff; font-weight: 700; text-align: center; font-size: 10pt; padding: 5px 8px; margin: 14px 0 0; }
        .section-title { font-size: 10pt; font-weight: 700; margin: 0 0 5px; }
        .page-break { break-before: page; page-break-before: always; padding-top: 2px; }
        .page-head { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; border-bottom: 1.5px solid #1a1a1a; padding-bottom: 5px; margin-bottom: 12px; }
        .page-head .ph-title { font-size: 11pt; font-weight: 800; }
        .page-head .ph-meta { font-size: 8.5pt; color: #666; white-space: nowrap; }
        .bar-track { background: #f0f0f0; height: 11px; width: 100%; border: 1px solid #e2e2e2; }
        .bar-fill { height: 100%; }

        table { width: 100%; border-collapse: collapse; }
        table.info-bar { margin: 0 0 10px; }
        table.info-bar td { border: 1px solid #cfcfcf; padding: 4px 7px; vertical-align: middle; text-align: center; }
        .ib-label { font-size: 8pt; color: #888; display: block; margin-bottom: 2px; }
        .ib-value { font-size: 9.5pt; font-weight: 700; }
        .meta-row { text-align: center; margin: 0 0 16px; }
        .meta-row .meta-item { display: inline-block; margin: 0 9px; font-size: 9.5pt; vertical-align: middle; }
        .meta-row .lbl { font-weight: 700; }
        .meta-row .val-box { display: inline-block; border: 1px solid #cfcfcf; background: #fafafa; padding: 3px 14px; margin: 0 4px; min-width: 64px; text-align: center; font-weight: 700; }
        .info-section { margin: 0 0 12px; padding: 7px 10px; border: 1px solid #cfcfcf; }
        .info-label { font-size: 9pt; font-weight: 700; margin-bottom: 4px; }
        .info-section p { font-size: 8.5pt; margin: 2px 0; color: #444; }
        .period-info { background: #f1f5f9; padding: 10px 12px; margin: 10px 0 0; font-size: 9pt; }
        .period-info strong { margin-inline-end: 4px; }

        table.dual { table-layout: fixed; margin: 0 0 10px; }
        table.dual > tbody > tr > td.dual-col, table.dual > tbody > tr > td.dual-gap { vertical-align: top; padding: 0; border: none; }
        td.dual-gap { width: 16px; }

        table.report-table { table-layout: fixed; margin: 0; }
        table.report-table th, table.report-table td { border: 1px solid #cfcfcf; padding: 4px 6px; font-size: 8.5pt; overflow-wrap: break-word; vertical-align: middle; text-align: center; }
        table.report-table thead th { background: #ededed; font-weight: 700; }
        table.report-table tbody th { font-weight: 700; }
        table.report-table tfoot td, table.report-table tfoot th { background: #f2f2f2; font-weight: 700; border-top: 1.5px solid #1a1a1a; }
        table.report-table tr.net-row th, table.report-table tr.net-row td { background: #1a1a1a; color: #fff; font-weight: 700; border-color: #fff; }
        table.report-table.info-box thead th { background: #1a1a1a; color: #fff; border-color: #fff; }
        table.report-table.info-box tr.net-row th, table.report-table.info-box tr.net-row td { background: #ededed; color: #1a1a1a; border-color: #cfcfcf; }
        /* رأس الجدول يتكرر في كل صفحة، والمجموع مرة في آخره، ولا ينقسم سطر بين صفحتين. */
        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr, .report-stats, .summary-box, .sig-table, .amount-words { break-inside: avoid; }
        .col-text { text-align: right; }
        .col-num { text-align: left; white-space: nowrap; }
        thead th.col-num { white-space: normal; }
        .block, table.report-table.block { margin: 0 0 10px; }

        /* البطاقات بعرض متساوٍ داخل الورقة؛ كثرتها تصغّر الرقم بدل أن تتجاوز الهامش. */
        .report-stats { table-layout: fixed; border-collapse: separate; border-spacing: 7px 0; margin: 0 0 12px; }
        .report-stats td { padding: 0; text-align: center; vertical-align: top; border: none; }
        .report-stat-label { font-size: 8.5pt; font-weight: 700; margin-bottom: 4px; }
        .report-stat-value { font-size: 12pt; font-weight: 700; border: 1px solid #cfcfcf; padding: 6px 4px; }
        .report-stats.dense .report-stat-label { font-size: 7.5pt; }
        .report-stats.dense .report-stat-value { font-size: 9.5pt; padding: 6px 2px; }

        .bottom-section { margin-top: 24px; }
        .summary-row { table-layout: fixed; border-collapse: collapse; font-size: 9pt; margin-bottom: -1px; }
        .summary-row td { padding: 6px 12px; border: 1px solid #cfcfcf; }
        .summary-row td + td { width: 35%; text-align: left; font-weight: 600; }
        .summary-row.highlight td { background: #1a1a1a; color: #fff; font-weight: 700; border-color: #1a1a1a; }

        table.amount-words { margin: 0 0 10px; }
        table.amount-words td { border: 1px solid #cfcfcf; }
        .aw-cap { background: #1a1a1a; color: #fff; font-weight: 700; text-align: center; padding: 5px 8px; font-size: 9pt; }
        .aw-text { padding: 7px 10px; font-size: 9.5pt; }

        table.sig-table { margin: 26px 0 4px; }
        table.sig-table td { border: none; text-align: center; vertical-align: bottom; padding: 0 8px; }
        .sig-label { font-size: 9pt; font-weight: 700; margin-bottom: 18px; }
        .sig-line { font-size: 9pt; color: #888; letter-spacing: 1px; }
        table.report-footer { margin: 18px 0 0; border-top: 1px solid #cfcfcf; }
        table.report-footer td { border: none; padding: 8px 4px 0; text-align: center; font-size: 8pt; color: #888; }

        ul.cf-notes { margin: 0; padding-right: 16px; list-style-type: disc; }
        ul.cf-notes li { font-size: 8pt; color: #444; margin-bottom: 5px; line-height: 1.5; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 8pt; font-weight: 600; color: #fff; }
        .bg-success { background: #198754; }
        .bg-danger { background: #dc3545; }
        .tx-good { color: #198754; }
        .tx-bad { color: #dc3545; }
        .muted { color: #888; }
        .nowrap { white-space: nowrap; }
        .money { white-space: nowrap; }
        .money small { margin-inline-start: 3px; font-size: .8em; }
        .note { font-size: 8.5pt; color: #64748b; margin-top: 10px; }

        @media screen {
            body { background: #525659; padding: 0 0 40px; }
            .report-toolbar { position: sticky; top: 0; z-index: 50; display: flex; justify-content: center; gap: 10px; padding: 11px; margin-bottom: 22px; background: #fff; border-bottom: 1px solid #d9d9d9; }
            .report-toolbar button { font-family: inherit; font-size: 10pt; font-weight: 700; cursor: pointer; border: 1px solid #d9d9d9; padding: 9px 24px; }
            .report-toolbar .rt-print { background: #1a1a1a; border-color: #1a1a1a; color: #fff; }
            .report-toolbar .rt-close { background: #efefef; color: #1a1a1a; }
            .report-page { width: @yield('page-width', '210mm'); min-height: @yield('page-height', '297mm'); margin: 0 auto; padding: 12mm 10mm; background: #fff; }
        }
        @media print {
            .report-toolbar { display: none !important; }
            .report-page { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="report-toolbar">
        <button type="button" class="rt-print" onclick="window.print()">طباعة / حفظ PDF</button>
        <button type="button" class="rt-close" onclick="window.close()">إغلاق</button>
    </div>
    <div class="report-page">
        @yield('content')
    </div>
</body>
</html>
