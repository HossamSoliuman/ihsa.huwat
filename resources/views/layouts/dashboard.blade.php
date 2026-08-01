<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d2835">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/employment.css') }}">
    @stack('styles')
</head>
<body class="@yield('body-class')">
<div class="layout">
    <header class="topbar">
        <div class="topbar-brand"><a href="{{ route('home') }}" class="brand-link"><span class="brand-mark"></span><span class="brand-copy"><span class="brand-text">إحصاء المصيد</span><span class="brand-subtitle">منصة حوات</span></span></a></div>
        <button class="sidebar-toggle icon-button" id="sidebarToggle" type="button" aria-label="فتح القائمة"><span class="hamburger"><i></i><i></i><i></i></span></button>
        <div class="topbar-spacer"></div>
        <button class="theme-toggle icon-button topbar-action" id="themeToggle" type="button" aria-label="تبديل المظهر">◐</button>
        <div class="topbar-user"><span class="user-avatar">{{ mb_substr(auth()->user()->full_name, 0, 1) }}</span><span class="user-meta"><span class="user-name">{{ auth()->user()->full_name }}</span><span class="user-role">{{ auth()->user()->role->name_ar }}</span></span></div>
    </header>
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            @php($lastGroup = null)
            @foreach(config('dashboard.navigation') as $item)
                @continue(!in_array(auth()->user()->role->code, $item['roles'], true))
                @if($lastGroup !== $item['group']) @php($lastGroup = $item['group']) <div class="sidebar-section">{{ $lastGroup }}</div> @endif
                <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}"><span class="nav-icon" data-icon="{{ $item['icon'] }}"></span><span>{{ $item['label'] }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-footer">
            <form class="logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="nav-link logout-link" type="submit">
                    <span class="nav-icon" data-icon="log-out"></span>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </div>
    </aside>
    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="إغلاق القائمة"></button>
    <div class="main"><main class="content">
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main></div>
</div>
<script src="{{ asset('assets/js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
