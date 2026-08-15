@php
    $pageLabel = collect(config('hawat.nav'))
        ->flatMap(fn ($section) => $section['items'])
        ->firstWhere('route', request()->route()?->getName())['label'] ?? 'الرئيسية';
@endphp
<header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar(true)">
        @include('partials.icon', ['name' => 'menu'])
    </button>
    <h2>{{ $pageLabel }}</h2>

    <div class="topbar-actions">
        <div class="search-box">
            @include('partials.icon', ['name' => 'search'])
            <input type="text" placeholder="بحث سريع...">
        </div>
        <button class="icon-btn" onclick="toggleTheme()" title="الوضع الداكن">
            @include('partials.icon', ['name' => 'moon'])
        </button>
        <button class="icon-btn">
            @include('partials.icon', ['name' => 'bell'])
            <span class="dot"></span>
        </button>
        <div class="user-chip">
            <div class="avatar">م</div>
            <div class="meta">
                <p class="role">مدير عام</p>
                <p class="sub">الإدارة العليا</p>
            </div>
        </div>
    </div>
</header>