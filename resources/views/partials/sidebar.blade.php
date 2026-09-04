@php
    use App\Support\Nav;
@endphp
<aside class="sidebar" id="sidebar">
    {{-- الشعار انتقل إلى الشريط العلوي، فلم يبقَ في رأس القائمة إلا زرّ الإغلاق — ولا يظهر إلا دون 1024px. --}}
    <div class="sidebar-head">
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

    {{-- ذيل القائمة: هويّة المستخدم وحدها — وزرّ الوضع الداكن عاد إلى الشريط العلوي. --}}
    <div class="sidebar-foot">
        <div class="user-chip">
            <div class="avatar">م</div>
            <div class="meta">
                <p class="role">مدير عام</p>
                <p class="sub">الإدارة العليا</p>
            </div>
        </div>
    </div>
</aside>
