<!doctype html>
<html lang="ar" dir="rtl" data-employment-page>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b2942">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <meta name="description" content="@yield('description', 'اكتشف فرص العمل المتاحة وقدّم طلبك إلكترونياً.')">
    <title>@yield('title', 'بوابة التوظيف') | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/employment.css') }}">
</head>
<body class="employment-public @yield('body-class')">
<a class="employment-skip-link" href="#main-content">تجاوز إلى المحتوى الرئيسي</a>
<header class="employment-header" data-public-header>
    <div class="employment-container employment-header-inner">
        <a class="employment-brand" href="{{ route('home') }}" aria-label="بوابة التوظيف - الصفحة الرئيسية">
            <span class="employment-brand-mark" aria-hidden="true"><svg viewBox="0 0 48 48"><path d="M24 4v29M17 12h14M12 22c0 8 5.4 14 12 14s12-6 12-14M8 24h8M32 24h8"></path><circle cx="24" cy="7" r="3"></circle><path d="M14 39c3-2 6-2 10 0s7 2 10 0"></path></svg></span>
            <span><strong>بوابة التوظيف</strong><small>نظام إحصاء المصيد وإدارة الموانئ</small></span>
        </a>
        @if(View::hasSection('simple-public-header'))
            @auth
                <a class="employment-button employment-button-outline" href="{{ route(auth()->user()->role->dashboard_route) }}">دخول لوحة التحكم</a>
            @else
                <a class="employment-button employment-button-outline" href="{{ route('login') }}">دخول الموظفين</a>
            @endauth
        @else
            <button class="employment-nav-toggle" type="button" data-public-nav-toggle aria-controls="employment-public-nav" aria-expanded="false"><span class="sr-only">فتح قائمة التنقل</span><i></i><i></i><i></i></button>
            <nav class="employment-nav" id="employment-public-nav" aria-label="التنقل الرئيسي" data-public-nav>
                <a href="{{ route('home') }}">الرئيسية</a>
                <a href="{{ route('jobs.index') }}">الوظائف المتاحة</a>
                <a href="{{ route('home') }}#application-process">خطوات التقديم</a>
                <a href="{{ route('login') }}">دخول الموظفين</a>
                <button class="employment-theme-toggle" type="button" data-employment-theme-toggle aria-label="تبديل المظهر">◐</button>
            </nav>
        @endif
    </div>
</header>
<main id="main-content" class="employment-main" tabindex="-1">@yield('content')</main>
<footer class="employment-footer"><div class="employment-container"><p>نظام إحصاء المصيد وإدارة الموانئ</p><span>جميع الطلبات تُعامل بسرية</span></div></footer>
<script src="{{ asset('assets/js/employment.js') }}" defer></script>
</body>
</html>
