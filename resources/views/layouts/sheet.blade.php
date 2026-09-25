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
        صفحة A4 مستقلة لسندات بوابة المالك وكشوفها (على نسق فاتورة البيع):
        لا قائمة ولا شريط — تُطبع أو تُحفظ PDF من نافذة الطباعة.
    --}}
    <style>
        @page { size: A4 @yield('orientation'); margin: 12mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Chakra Petch', 'Tajawal', sans-serif; color: #0f172a; background: #eef2f6; font-size: 12px; line-height: 1.7; }
        .sheet { max-width: @yield('width', '800px'); margin: 24px auto; background: #fff; border-top: 4px solid #1d6fb8; padding: 32px 36px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 16px; border-bottom: 1px dashed #cbd5e1; }
        .head h1 { font-size: 17px; font-weight: 800; color: #1d6fb8; }
        .head p { color: #475569; font-size: 11px; }
        .title { text-align: center; font-size: 18px; font-weight: 800; color: #1d6fb8; margin: 18px 0; }
        .boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f1f5f9; padding: 14px 16px; margin-bottom: 18px; }
        .boxes h3 { font-size: 12px; color: #1d6fb8; margin-bottom: 4px; }
        .boxes dl { display: grid; grid-template-columns: auto 1fr; gap: 2px 10px; font-size: 11.5px; }
        .boxes dt { color: #64748b; }
        .chips { display: flex; flex-wrap: wrap; gap: 6px 14px; background: #f1f5f9; padding: 10px 14px; margin-bottom: 14px; font-size: 11px; }
        .chips b { color: #1d6fb8; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead th { background: #1d6fb8; color: #fff; font-weight: 600; font-size: 11px; padding: 7px 6px; text-align: right; }
        tbody td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 11px; vertical-align: top; }
        tfoot td { padding: 7px 6px; font-weight: 800; background: #f1f5f9; font-size: 11.5px; }
        .num { font-family: 'Chakra Petch', sans-serif; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .muted { color: #64748b; font-size: 10px; }
        .totals { margin-inline-start: auto; width: 300px; background: #f1f5f9; padding: 10px 14px; }
        .totals div { display: flex; justify-content: space-between; padding: 2px 0; }
        .totals .grand { border-top: 1px solid #cbd5e1; margin-top: 4px; padding-top: 6px; font-weight: 800; color: #1d6fb8; font-size: 14px; }
        .signs { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 36px; font-size: 11px; color: #475569; }
        .signs span { display: block; border-bottom: 1px solid #94a3b8; height: 28px; margin-bottom: 4px; }
        .foot { margin-top: 28px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; color: #64748b; font-size: 10.5px; }
        .toolbar { max-width: @yield('width', '800px'); margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button { font-family: inherit; font-weight: 700; border: 1px solid #1d6fb8; background: #1d6fb8; color: #fff; padding: 8px 16px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; max-width: none; padding: 0; border-top: 0; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button></div>
    <main class="sheet">
        @yield('content')
        <footer class="foot">
            <span>{{ config('hawat.name') }} — {{ config('hawat.sector') }}</span>
            <span class="num">طُبع {{ now()->format('Y-m-d H:i') }}</span>
        </footer>
    </main>
</body>
</html>
