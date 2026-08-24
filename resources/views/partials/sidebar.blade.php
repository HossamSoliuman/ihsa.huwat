@php
    use App\Support\Nav;
@endphp
<aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
        <img src="{{ config('hawat.logo') }}" alt="{{ config('hawat.name') }}">
        <div style="min-width:0">
            <p class="app-name">{{ config('hawat.name') }}</p>
        </div>
        <button class="sidebar-close" onclick="toggleSidebar(false)">
            @include('partials.icon', ['name' => 'x'])
        </button>
    </div>

    <nav class="sidebar-nav">
        @foreach (Nav::sections() as $section)
            <div class="nav-section">
                <p class="nav-section-title">{{ $section['title'] }}</p>
                @foreach ($section['items'] as $item)
                    <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                        @include('partials.icon', ['name' => $item['icon']])
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="sidebar-foot">
        @foreach (Nav::otherPortals() as $other)
            <a href="{{ route($other['home']) }}" class="portal-switch">
                @include('partials.icon', ['name' => $other['icon']])
                <span>الانتقال إلى {{ $other['label'] }}</span>
                @include('partials.icon', ['name' => 'chevron-left'])
            </a>
        @endforeach
        <div class="ministry-card">
            <div class="avatar">وز</div>
            <div style="min-width:0;flex:1">
                <p class="name">{{ config('hawat.ministry') }}</p>
                <p class="sub">{{ config('hawat.sector') }}</p>
            </div>
        </div>
    </div>
</aside>
