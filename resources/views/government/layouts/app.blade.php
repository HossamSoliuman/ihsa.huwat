<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d2835">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <title>@yield('title') | البوابة الحكومية</title>
    <link rel="stylesheet" href="{{ asset('assets/css/government.css') }}">
    @stack('styles')
</head>
<body class="government-portal @yield('body-class')">
@php($governmentUser = Auth::guard('government')->user())
<div class="layout">
    <header class="topbar government-topbar">
        <div class="topbar-brand">
            <a href="{{ route('government.dashboard') }}" class="brand-link">
                <span class="brand-mark"></span>
                <span class="brand-text">البوابة الحكومية</span>
            </a>
        </div>
        <button class="sidebar-toggle icon-button" id="sidebarToggle" type="button" aria-label="فتح القائمة" aria-expanded="false"><span class="hamburger"><i></i><i></i><i></i></span></button>
        <div class="government-portal-marker"><span></span>GOVERNMENT ACCESS</div>
        <div class="topbar-spacer"></div>
        <button class="theme-toggle icon-button topbar-action" id="themeToggle" type="button" aria-label="تبديل المظهر">◐</button>
        <div class="topbar-user">
            <span class="user-avatar">{{ mb_substr($governmentUser->full_name, 0, 1) }}</span>
            <span class="user-meta"><span class="user-name">{{ $governmentUser->full_name }}</span><span class="user-role">حساب البوابة الحكومية</span></span>
        </div>
    </header>

    <aside class="sidebar government-sidebar" id="sidebar">
        <div class="government-sidebar-head"><small>IHSA / GOV</small><strong>مركز القرار البحري</strong><span>جلسة حكومية مستقلة</span></div>
        <nav class="sidebar-nav">
            @php($lastGroup = null)
            @foreach(config('government.navigation') as $item)
                @if($lastGroup !== $item['group']) @php($lastGroup = $item['group']) <div class="sidebar-section">{{ $lastGroup }}</div> @endif
                <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}"><span class="nav-icon" data-icon="{{ $item['icon'] }}"></span><span>{{ $item['label'] }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-footer">
            <form class="logout-form" method="POST" action="{{ route('government.logout') }}">
                @csrf
                <button class="nav-link logout-link" type="submit"><span class="nav-icon" data-icon="log-out"></span><span>تسجيل الخروج</span></button>
            </form>
        </div>
    </aside>

    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="إغلاق القائمة"></button>
    <div class="main"><main class="content">
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main></div>
</div>
<script src="{{ asset('assets/js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>
