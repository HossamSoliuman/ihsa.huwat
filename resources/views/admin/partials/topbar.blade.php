{{--
    الشريط العلوي هو نفسه شريط لوحة الوزارة: لوحٌ أزرق مصمت يمتدّ بعرض الصفحة
    فوق القائمة الجانبية، الشعار في أوّله وأدواته في آخره. يزيد عليه هنا زرّ
    الخروج — فهذه البوابة وحدها خلف تسجيل دخول.
--}}
<header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar(true)" aria-label="القائمة">
        @include('admin.partials.icon', ['name' => 'menu'])
    </button>

    <a class="topbar-brand" href="{{ route('admin.index') }}">
        {{-- الشريط أزرق داكن في الوضعين، فالنسخة البيضاء وحدها تصلح عليه. --}}
        <img src="{{ asset('images/logo-white.png') }}" alt="{{ config('hawat.name') }}">
    </a>

    <div class="topbar-actions">
        <button class="icon-btn" onclick="toggleTheme()" title="تبديل الوضع" aria-label="تبديل الوضع">
            @include('admin.partials.icon', ['name' => 'moon'])
        </button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="icon-btn" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                @include('admin.partials.icon', ['name' => 'log-out'])
            </button>
        </form>
    </div>
</header>
