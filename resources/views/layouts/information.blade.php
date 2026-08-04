<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d2835">
    <meta name="description" content="بوابة حوات لتسجيل بيانات القوارب والصيادين بصورة آمنة ومنظمة.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <title>@yield('title', 'بوابة السجلات البحرية') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="info-shell min-h-screen font-sans antialiased">
    <a class="info-skip-link" href="#main-content">تجاوز إلى المحتوى</a>

    <div class="info-atmosphere" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <header class="info-topbar">
        <div class="info-topbar-inner">
            <a class="info-brand" href="{{ route('information.create') }}" aria-label="بوابة حوات للسجلات البحرية">
                <span class="info-brand-mark">
                    <img src="{{ asset('assets/img/hud/hawat-logo.png') }}" alt="">
                </span>
                <span class="info-brand-copy">
                    <strong>السجلات البحرية</strong>
                    <small>منصة حوات · إحصاء المصيد</small>
                </span>
            </a>

            <div class="info-account">
                <div class="info-account-card">
                    <span class="info-account-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span>
                    <span class="info-account-copy">
                        <small>المستخدم الحالي</small>
                        <strong>{{ auth()->user()->full_name }}</strong>
                    </span>
                </div>

                <a class="info-icon-button" href="{{ route(auth()->user()->role->dashboard_route) }}" aria-label="العودة إلى لوحة التحكم" title="العودة إلى لوحة التحكم">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9.5 20v-6h5v6"></path>
                    </svg>
                </a>

                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="info-icon-button" type="submit" aria-label="تسجيل الخروج" title="تسجيل الخروج">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M10 17l5-5-5-5M15 12H3m9-8h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main id="main-content" tabindex="-1">@yield('content')</main>

    <footer class="info-footer">
        <div class="info-footer-inner">
            <p>منصة حوات لإدارة البيانات البحرية</p>
            <p><span aria-hidden="true">●</span> اتصال آمن · تحفظ البيانات عند الإرسال فقط</p>
        </div>
    </footer>
</body>
</html>
