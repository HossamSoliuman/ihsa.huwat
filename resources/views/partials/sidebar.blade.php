@php
    use App\Support\Nav;
@endphp
<aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
        <img class="mark-light" src="{{ asset('images/logo.png') }}" alt="{{ config('hawat.name') }}">
        <img class="mark-dark" src="{{ asset('images/logo-white.png') }}" alt="" aria-hidden="true">
        <p class="app-name">{{ config('hawat.name') }}</p>
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

    {{--
        ذيل القائمة يرث ما كان في الشريط العلوي بعد إزالته: هويّة المستخدم
        وزرّ الوضع الداكن. ولا ثالث لهما — البحث والتنبيهات كانا واجهةً بلا
        وظيفة خلفها فسقطا معه.
    --}}
    <div class="sidebar-foot">
        <div class="user-chip">
            <div class="avatar">م</div>
            <div class="meta">
                <p class="role">مدير عام</p>
                <p class="sub">الإدارة العليا</p>
            </div>
        </div>
        <button class="icon-btn" onclick="toggleTheme()" title="الوضع الداكن">
            @include('partials.icon', ['name' => 'moon'])
        </button>
    </div>
</aside>
