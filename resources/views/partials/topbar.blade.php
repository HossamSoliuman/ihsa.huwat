{{--
    الشريط العلوي على شاكلة hispa: لوحٌ أزرق مصمت يمتدّ بعرض الصفحة كلّها فوق
    القائمة الجانبية، الشعار في أوّله وأزرار الأدوات في آخره. لا اسم صفحة فيه —
    ترويسة الصفحة تحمله أصلًا، فتكراره هنا حشو.
--}}
<header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar(true)" aria-label="القائمة">
        @include('partials.icon', ['name' => 'menu'])
    </button>

    <a class="topbar-brand" href="{{ route(App\Support\Nav::portal()['home']) }}">
        {{-- الشريط أزرق داكن في الوضعين، فالنسخة البيضاء وحدها تصلح عليه. --}}
        <img src="{{ asset('images/logo-white.png') }}" alt="{{ config('hawat.name') }}">
    </a>

    <div class="topbar-actions">
        <button class="icon-btn" onclick="toggleTheme()" title="الوضع الداكن" aria-label="الوضع الداكن">
            @include('partials.icon', ['name' => 'moon'])
        </button>
    </div>
</header>
