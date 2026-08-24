<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('hawat.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tajawal', 'Segoe UI', Tahoma, sans-serif; color: #0f172a; font-size: 11px; background: #fff; padding: 8px; }
        h1 { font-size: 19px; color: #0c4a6e; }
        h2 { font-size: 14px; color: #0369a1; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; margin: 18px 0 8px; }
        .doc-head { text-align: center; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; margin-bottom: 16px; }
        .doc-head .sub { margin-top: 4px; font-size: 13px; color: #64748b; }
        .doc-head .meta { margin-top: 4px; font-size: 10px; color: #94a3b8; }
        .totals { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 18px; }
        .totals .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; text-align: center; }
        .totals .card .v { font-size: 17px; font-weight: 700; color: #0369a1; }
        .totals .card .l { margin-top: 2px; font-size: 10px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        th { background: #0369a1; color: #fff; padding: 6px 7px; text-align: right; font-weight: 700; white-space: nowrap; }
        td { border: 1px solid #e2e8f0; padding: 5px 7px; text-align: right; vertical-align: top; word-break: break-word; }
        tr:nth-child(even) td { background: #f8fafc; }
        .count { display: inline-block; border-radius: 999px; background: #e0f2fe; color: #075985; padding: 1px 9px; font-size: 10px; font-weight: 700; }
        .empty { padding: 24px; text-align: center; color: #94a3b8; }
        .doc-foot { margin-top: 22px; border-top: 1px solid #e2e8f0; padding-top: 8px; text-align: center; font-size: 10px; color: #94a3b8; }
        @media print { .no-print { display: none !important; } }
    </style>
    @stack('print-styles')
</head>
<body>
    @yield('content')
    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 350));
    </script>
</body>
</html>
