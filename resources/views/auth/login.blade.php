<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — {{ config('info.title') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    <style>
        {{-- صفحة الدخول شاشة واحدة: ترويسة، ثم البطاقة في وسطها، ثم حقوق الوزارة. --}}
        .auth-page {
            min-height: 100dvh;
            display: grid; grid-template-rows: auto 1fr auto; gap: 1.5rem;
            padding: 1.5rem clamp(1rem, 3vw, 2.5rem);
        }
        .auth-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .auth-mark img { height: clamp(28px, 4vh, 38px); width: auto; display: block; }
        {{-- نسخة الشعار البيضاء للوضع الليلي، والزرقاء للنهاري. --}}
        .auth-mark .mark-dark { display: none; }
        html.dark .auth-mark .mark-light { display: none; }
        html.dark .auth-mark .mark-dark { display: block; }

        .auth-main { display: flex; align-items: center; justify-content: center; }
        .auth-card { width: min(26rem, 100%); padding: 1.75rem 1.6rem; gap: 1.1rem; }

        {{-- ترويسة البطاقة: مربّع الأيقونة نفسه الذي في ترويسات الصفحات. --}}
        .auth-brand { display: flex; align-items: center; gap: .7rem; }
        .auth-brand .ico {
            display: flex; align-items: center; justify-content: center; height: 2.5rem; width: 2.5rem; flex-shrink: 0;
            background: hsl(var(--primary) / .1); border: 1px solid hsl(var(--primary) / .45); color: hsl(var(--primary));
        }
        .auth-brand .ico svg { width: 20px; height: 20px; }
        .auth-brand h1 { font-size: 1.05rem; font-weight: 700; line-height: 1.35; }

        .auth-form { display: flex; flex-direction: column; gap: .85rem; }
        .auth-form .input { padding: .55rem .7rem; }
        .auth-form .btn { justify-content: center; padding: .6rem .85rem; }
        {{-- خانة التذكّر سطر واحد: المربّع ثم نصّه، بلا عمود. --}}
        .auth-remember { display: flex; align-items: center; gap: .5rem; font-size: .76rem; color: hsl(var(--muted-foreground)); cursor: pointer; }
        .auth-remember input { width: .95rem; height: .95rem; accent-color: hsl(var(--primary)); cursor: pointer; }

        {{-- الخطأ بلون الحالة الحرجة، على قاعدة .flash نفسها لكن بلونها. --}}
        .auth-error { border: 1px solid hsl(352 80% 50% / .45); background: hsl(352 80% 50% / .1); color: #be123c; padding: .55rem .8rem; font-size: .78rem; line-height: 1.8; }
        html.dark .auth-error { color: hsl(352 85% 72%); }

        .auth-foot { font-size: .72rem; color: hsl(var(--muted-foreground)); text-align: center; }
    </style>
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="auth-page">
        <header class="auth-head">
            <span class="auth-mark">
                <img class="mark-light" src="{{ asset('images/logo.png') }}" alt="{{ config('hawat.name') }}">
                <img class="mark-dark" src="{{ asset('images/logo-white.png') }}" alt="" aria-hidden="true">
            </span>

            <button type="button" class="icon-btn" onclick="toggleTheme()" title="تبديل الوضع" aria-label="تبديل الوضع">
                @include('partials.icon', ['name' => 'moon'])
            </button>
        </header>

        <main class="auth-main">
            <div class="card auth-card">
                <div class="auth-brand">
                    <div class="ico">@include('partials.icon', ['name' => 'shield-check'])</div>
                    <h1>{{ config('info.title') }}</h1>
                </div>

                @if ($errors->any())
                    <div class="auth-error">{{ $errors->first() }}</div>
                @endif

                <form class="auth-form" method="POST" action="{{ route('login') }}">
                    @csrf

                    <label class="field">
                        <span>البريد الإلكتروني</span>
                        <input class="input" type="email" name="email" value="{{ old('email') }}"
                               dir="ltr" autocomplete="username" required autofocus>
                    </label>

                    <label class="field">
                        <span>كلمة المرور</span>
                        <input class="input" type="password" name="password"
                               dir="ltr" autocomplete="current-password" required>
                    </label>

                    <label class="auth-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        تذكُّرني على هذا الجهاز
                    </label>

                    <button type="submit" class="btn btn-primary">
                        @include('partials.icon', ['name' => 'log-in'])
                        دخول
                    </button>
                </form>
            </div>
        </main>

        <footer class="auth-foot">
            جميع الحقوق محفوظة لوزارة البيئة والمياه والزراعة، وكالة الوزارة لتقنية المعلومات والتحول الرقمي
        </footer>
    </div>
    <script>
        function toggleTheme() {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('hawat-theme', dark ? 'dark' : 'light');
        }
    </script>
</body>
</html>
