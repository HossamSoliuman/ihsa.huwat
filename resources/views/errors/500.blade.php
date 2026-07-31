<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>تعذر إكمال الطلب | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="server-error-page">
    <main class="server-error-card">
        <div class="server-error-code" aria-hidden="true">SYSTEM · 500</div>
        <h1>تعذر إكمال الطلب</h1>
        <p>حدث خطأ غير متوقع. تم تسجيل المشكلة، ويمكنك المحاولة مرة أخرى بعد قليل.</p>
        <a class="server-error-action" href="{{ url('/') }}">العودة إلى الصفحة الرئيسية</a>
    </main>
</body>
</html>
