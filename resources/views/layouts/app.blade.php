<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'الرئيسية') — {{ config('hawat.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    <script>
        if (localStorage.getItem('hawat-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="shell">
        @include('partials.sidebar')
        <div class="backdrop" id="backdrop" onclick="toggleSidebar(false)"></div>
        <div class="main">
            @include('partials.topbar')
            <main class="content">
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
        function toggleDrawer(id, open) {
            document.getElementById(id).classList.toggle('is-open', open);
            document.getElementById(id + '-overlay').classList.toggle('is-open', open);
        }

        // القائمة الجانبية أطول من الشاشة: نُوسّط الرابط النشط داخلها حتى لا يفتح
        // التبويب وهو خارج مجال العرض.
        function centerActiveNavLink(box) {
            const active = box?.querySelector('.is-active');

            if (!active) {
                return;
            }

            const offset = active.getBoundingClientRect().top - box.getBoundingClientRect().top;
            box.scrollTop += offset - (box.clientHeight - active.offsetHeight) / 2;
        }

        centerActiveNavLink(document.querySelector('.sidebar-nav'));
    </script>
    @stack('scripts')
</body>
</html>