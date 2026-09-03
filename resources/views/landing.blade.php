<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('hawat.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    <style>
        .landing-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .landing-brand img { width: min(60vw, 60vh); height: auto; max-width: 100%; object-fit: contain; }
    </style>
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="landing-page">
        <div class="landing-brand">
            <img src="{{ config('hawat.logo') }}" alt="{{ config('hawat.name') }}">
        </div>
    </div>
</body>
</html>
