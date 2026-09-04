<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('info.title')) — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{--
        الخطّان نفسهما اللذان في لوحة الوزارة: Chakra Petch للأرقام واللاتيني،
        و Tajawal للعربية لأن الأول بلا حروف عربية فتلتقطها الثانية تلقائيًا.
    --}}
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    {{--
        نظام التصميم واحد للبوابتين: ملف اللوحة أولًا بمتغيّراته وأصنافه كاملة،
        ثم ملف البوابة بما تنفرد به وحدها (التبويبات والجداول والنموذج المنبثق).
    --}}
    @include('partials.styles')
    @include('admin.partials.styles')
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="shell">
        @include('admin.partials.topbar')
        @include('admin.partials.sidebar')
        <div class="backdrop" id="backdrop" onclick="toggleSidebar(false)"></div>

        <div class="main">
            <main class="content">
                @if (session('status'))
                    <div class="flash">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="flash flash-error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    <script>
        function toggleSidebar(open) {
            document.getElementById('sidebar').classList.toggle('is-open', open);
            document.getElementById('backdrop').classList.toggle('is-visible', open);
        }
        function toggleTheme() {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('hawat-theme', dark ? 'dark' : 'light');
        }

        // القائمة أطول من الشاشة: نُوسّط التبويب النشط داخلها حتى لا يفتح وهو
        // خارج مجال العرض.
        (function () {
            const box = document.querySelector('.sidebar-nav');
            const active = box?.querySelector('.is-active');

            if (!active) {
                return;
            }

            const offset = active.getBoundingClientRect().top - box.getBoundingClientRect().top;
            box.scrollTop += offset - (box.clientHeight - active.offsetHeight) / 2;
        })();
    </script>
    @stack('scripts')
</body>
</html>
