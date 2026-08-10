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
    @use('App\Actions\Information\Support\InformationScope')
    @php
        /**
         * The desk sees every section; a moderator sees the ones its level answers for. The
         * scope is the same object the routes are guarded with, so a link that is hidden here
         * is a link that refuses to open anyway.
         */
        $scope = auth()->user()->informationScope();
    @endphp

    <a class="info-skip-link" href="#main-content">تجاوز إلى المحتوى</a>

    <div class="info-atmosphere" aria-hidden="true"></div>

    <div class="info-admin-layout">
        <aside class="info-admin-sidebar">
            <a class="info-brand" href="{{ route('information.admin.dashboard') }}" aria-label="لوحة مركز المعلومات">
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

                @if ($scope->allows(InformationScope::SUBMISSIONS))
                    <a href="{{ route('information.admin.index') }}"
                       class="info-admin-nav-link @if (request()->routeIs('information.admin.index')) is-active @endif"
                       @if (request()->routeIs('information.admin.index')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg>
                        <span>الصيادين والبحارة</span>
                        @isset($statusCounts)
                            <b>{{ $statusCounts['all'] ?? 0 }}</b>
                        @endisset
                    </a>
                @endif

                @if ($scope->allows(InformationScope::PORTS))
                    <a href="{{ route('information.admin.ports.index') }}"
                       class="info-admin-nav-link @if (request()->routeIs('information.admin.ports.*')) is-active @endif"
                       @if (request()->routeIs('information.admin.ports.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v13m0 0c-4 0-7.5-3.4-8-8h3m5 8c4 0 7.5-3.4 8-8h-3M12 8a2.2 2.2 0 1 0 0-4.4A2.2 2.2 0 0 0 12 8Zm-4 3.5h8"></path></svg>
                        <span>الموانئ</span>
                    </a>
                @endif

                @if ($scope->allows(InformationScope::MARKETS))
                    <a href="{{ route('information.admin.markets.index') }}"
                       class="info-admin-nav-link @if (request()->routeIs('information.admin.markets.*')) is-active @endif"
                       @if (request()->routeIs('information.admin.markets.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9h18l-1.5 11a2 2 0 0 1-2 1.8H6.5a2 2 0 0 1-2-1.8Z"></path><path d="M8 9V6a4 4 0 0 1 8 0v3"></path></svg>
                        <span>أسواق السمك</span>
                    </a>
                @endif

                @if ($scope->allows(InformationScope::BROKERS))
                    <a href="{{ route('information.admin.brokers.index') }}"
                       class="info-admin-nav-link @if (request()->routeIs('information.admin.brokers.*')) is-active @endif"
                       @if (request()->routeIs('information.admin.brokers.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7h-5V5a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v2H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"></path><path d="M2 12h20"></path></svg>
                        <span>الدلالين</span>
                    </a>
                @endif

                @if ($scope->allows(InformationScope::MODERATORS))
                    <a href="{{ route('information.admin.moderators.index') }}"
                       class="info-admin-nav-link is-standalone @if (request()->routeIs('information.admin.moderators.*')) is-active @endif"
                       @if (request()->routeIs('information.admin.moderators.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 6a7 7 0 0 0-14 0"></path><path d="m18.5 3.5 1.2 2.4 2.6.4-1.9 1.8.5 2.6-2.4-1.3-2.4 1.3.5-2.6-1.9-1.8 2.6-.4Z"></path></svg>
                        <span>المشرفون</span>
                    </a>
                @endif

                @if ($scope->allows(InformationScope::SETTINGS))
                    <a href="{{ route('information.admin.lookups.index') }}"
                       class="info-admin-nav-link @if (request()->routeIs('information.admin.lookups.*')) is-active @endif"
                       @if (request()->routeIs('information.admin.lookups.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                        <span>الإعدادات</span>
                    </a>
                @endif
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
