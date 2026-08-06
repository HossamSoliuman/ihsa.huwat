<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d2835">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <title>@yield('title', 'مركز المعلومات') | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="info-shell info-admin min-h-screen font-sans antialiased">
    <a class="info-skip-link" href="#main-content">تجاوز إلى المحتوى</a>

    <div class="info-atmosphere" aria-hidden="true"></div>

    <div class="info-admin-layout">
        <aside class="info-admin-sidebar">
            <a class="info-brand" href="{{ route('information.admin.index') }}" aria-label="لوحة مركز المعلومات">
                <span class="info-brand-mark">
                    <img src="{{ asset('assets/img/hud/hawat-logo.png') }}" alt="">
                </span>
                <span class="info-brand-copy">
                    <strong>مركز المعلومات</strong>
                    <small>منصة حوات</small>
                </span>
            </a>

            <nav class="info-admin-nav" aria-label="أقسام مركز المعلومات">
                <a href="{{ route('information.admin.dashboard') }}"
                   class="info-admin-nav-link @if (request()->routeIs('information.admin.dashboard')) is-active @endif"
                   @if (request()->routeIs('information.admin.dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9m5 10V5m5 14v-7m5 7V3"></path></svg>
                    <span>لوحة التحكم</span>
                </a>

                <p class="info-admin-nav-label">طلبات الصيادين والبحارة</p>
                @php
                    $activeStatus = request()->query('status');
                    $navItems = [
                        ['status' => null, 'label' => 'كل الطلبات'],
                        ['status' => 'submitted', 'label' => 'الطلبات الجديدة'],
                        ['status' => 'under_review', 'label' => 'تحت المراجعة'],
                        ['status' => 'needs_edit', 'label' => 'بانتظار التعديل'],
                        ['status' => 'approved', 'label' => 'المعتمدة'],
                        ['status' => 'rejected', 'label' => 'المرفوضة'],
                    ];
                @endphp

                @foreach ($navItems as $navItem)
                    @php
                        $isActive = request()->routeIs('information.admin.index') && $activeStatus === $navItem['status'];
                    @endphp
                    <a href="{{ route('information.admin.index', array_filter(['status' => $navItem['status']])) }}"
                       class="info-admin-nav-link @if ($isActive) is-active @endif"
                       @if ($isActive) aria-current="page" @endif>
                        <span class="info-admin-nav-dot" data-status="{{ $navItem['status'] ?? 'all' }}" aria-hidden="true"></span>
                        <span>{{ $navItem['label'] }}</span>
                        @isset($statusCounts)
                            <b>{{ $statusCounts[$navItem['status'] ?? 'all'] ?? 0 }}</b>
                        @endisset
                    </a>
                @endforeach

                <a href="{{ route('information.admin.lookups.index') }}"
                   class="info-admin-nav-link is-standalone @if (request()->routeIs('information.admin.lookups.*')) is-active @endif"
                   @if (request()->routeIs('information.admin.lookups.*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                    <span>الإعدادات</span>
                </a>
            </nav>

            <div class="info-admin-sidebar-footer">
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="info-admin-nav-link" type="submit">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3m9-8h6a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-6"></path></svg>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>

                <p class="info-admin-user">
                    <span class="info-account-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span>
                    <span>{{ auth()->user()->full_name }}</span>
                </p>
            </div>
        </aside>

        <main id="main-content" class="info-admin-main" tabindex="-1">
            @if (session('status'))
                <p class="info-admin-flash" role="status">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
                    {{ session('status') }}
                </p>
            @endif

            @if ($errors->any())
                <div class="info-admin-flash is-error" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"></path></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
