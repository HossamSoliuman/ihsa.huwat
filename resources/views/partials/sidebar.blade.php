@php
    use App\Support\Nav;

    $user = auth()->user();
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

    {{--
        ذيل القائمة: هويّة المستخدم — الداخل باسمه ودوره وزرّ خروجه، والزائر
        (بوابات الوزارة المفتوحة) بهويّة المشاهدة الافتراضية.
    --}}
    <div class="sidebar-foot">
        <div class="user-chip">
            <div class="avatar">{{ $user?->initial ?? 'م' }}</div>
            <div class="meta">
                <p class="role">{{ $user?->name ?? 'مدير عام' }}</p>
                <p class="sub">{{ $user?->display_role ?? 'الإدارة العليا' }}</p>
            </div>
        </div>
        @if ($user && Nav::portalKey() === Nav::OPS)
            <form method="POST" action="{{ route('panel.logout') }}">
                @csrf
                <button type="submit" class="icon-action" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                    @include('partials.icon', ['name' => 'log-out'])
                </button>
            </form>
        @endif
    </div>
</aside>
