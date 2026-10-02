@php
    use App\Models\Role;
    use App\Support\Nav;

    /*
     * الصفحة تُقرأ في شاشة واحدة بلا تمرير، فوصف كل بوابة سطر قصير والوسوم
     * وحدها تفصّل ما بداخلها.
     *
     * أربع بوابات في منتجين، لكلٍّ عنوانه: بوابات الوزارة الثلاث، ثم تطبيق حوات.
     * وسوم بوابات الوزارة مجموعات قائمتها، ووسوم التطبيق أدواره — قائمته
     * تتبدّل مع الدور فلا تصف التطبيق مجموعاتُ دورٍ واحد.
     */
    $card = function (string $key, string $blurb, ?array $tags = null): array {
        $portal = Nav::portal($key);

        return [
            'label' => $portal['label'],
            'icon' => $portal['icon'],
            'href' => route($portal['home']),
            'blurb' => $blurb,
            'tags' => $tags ?? array_column(Nav::sections($key), 'title'),
            // الإحصاء والخدمات مفتوحتان، وإدارة النظام والتطبيق خلف الدخول.
            'guarded' => in_array($key, [Nav::SUBADMIN, Nav::OPS], true),
        ];
    };

    $groups = [
        [
            'title' => 'الوزارة',
            'portals' => [
                $card(Nav::STATS, 'شاشة العرض والمؤشرات والرصد والتحليلات والتقارير.'),
                $card(Nav::SERVICES, 'طلبات الصيادين والرخص والامتثال والدعم الفني.'),
                $card(Nav::SUBADMIN, 'المستخدمون والموظفون والبيانات الأساسية والتكاملات.'),
            ],
        ],
        [
            'title' => 'تطبيق حوات',
            'portals' => [
                // اسم كل دور عنوانُ قائمته الأولى في nav_panel.
                $card(Nav::OPS, 'لوحة التطبيق على الويب، وقائمتها حسب دور الحساب.', array_map(
                    fn (string $role) => config("hawat.nav_panel.{$role}.0.title"),
                    [Role::OWNER, Role::CAPTAIN, Role::COUNTER, Role::DALAL, Role::MERCHANT],
                )),
            ],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البوابة — {{ config('hawat.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('partials.styles')
    <style>
        html, body { height: 100%; }
        {{-- الصفحة كلها في نافذة واحدة: ترويسة، ثم البوابات، ثم حقوق حوات. وعلى
             الشاشة الضيقة تتراصّ الصناديق فتُمرَّر بدل أن تُقصّ. --}}
        .portal-page {
            min-height: 100dvh;
            display: grid; grid-template-rows: auto 1fr auto; gap: 1.5rem;
            padding: 1.5rem clamp(1rem, 3vw, 2.5rem);
        }
        @media (min-width: 1200px) and (min-height: 700px) { .portal-page { height: 100dvh; overflow: hidden; } }
        .portal-head { display: flex; justify-content: center; }
        .hawat-mark img { height: clamp(40px, 6vh, 64px); width: auto; display: block; }
        {{-- نسخة الشعار البيضاء للوضع الليلي، والزرقاء للنهاري. --}}
        .hawat-mark .mark-dark { display: none; }
        .dark .hawat-mark .mark-light { display: none; }
        .dark .hawat-mark .mark-dark { display: block; }

        .portal-main { display: flex; align-items: center; justify-content: center; min-height: 0; }
        {{-- منتجان، لكلٍّ عنوانه: بوابات الوزارة الثلاث ثم تطبيق حوات. يتراصّان على
             الشاشة المتوسطة، ويتجاوران على العريضة فتصطفّ الأربع في صفّ واحد. --}}
        .portal-groups { display: grid; gap: 1.5rem; width: 100%; max-width: 30rem; }
        .portal-group { display: flex; flex-direction: column; gap: .75rem; min-width: 0; }
        .group-title {
            display: flex; align-items: center; gap: .6rem;
            font-size: .8rem; font-weight: 800; color: hsl(var(--primary));
        }
        .group-title::after { content: ''; flex: 1; border-top: 1px solid var(--hair); }
        .portal-grid { display: grid; gap: 1rem; flex: 1; }
        @media (min-width: 860px) {
            .portal-groups { max-width: 60rem; }
            .portal-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (min-width: 1200px) {
            .portal-groups { grid-template-columns: 3fr 1fr; gap: 2rem; max-width: 88rem; }
            .portal-group:last-child .portal-grid { grid-template-columns: 1fr; }
        }
        {{-- بوّابات الواجهة على قاعدة اللوحة نفسها التي في الداخل: سطحٌ شفّاف
             تمرّ خلفه صورة الصفحة، يحدّه خطٌّ شعري وأربعة أقواس زوايا — لا
             لونُ بطاقةٍ مصمت يقتطعها من الخلفية. والمرور يزيدها لمسةَ لونٍ
             خافتة لا رفعًا ولا ظلًّا. --}}
        .portal-card {
            display: flex; flex-direction: column; gap: .75rem; padding: 1.25rem;
            border: 1px solid var(--hair); border-radius: 0;
            background: var(--surface);
            transition: border-color .15s ease, background .15s ease;
        }
        .portal-card:hover { border-color: hsl(var(--primary) / .6); background: hsl(var(--primary) / .05); }
        {{-- رأس البطاقة سطر واحد: مربّع الأيقونة، ثم علامة البوابة المحميّة إن كانت. --}}
        .portal-card .card-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .portal-card .icon-wrap {
            display: flex; align-items: center; justify-content: center; height: 2.5rem; width: 2.5rem;
            border-radius: .75rem; background: hsl(var(--primary) / .12); color: hsl(var(--primary));
        }
        .portal-card .icon-wrap svg { width: 20px; height: 20px; }
        {{-- العلامة رقاقةٌ بلون التمييز لا قفلٌ مصمت: البوابة تُفتح بالدخول لا تُمنع. --}}
        .portal-lock {
            display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .45rem;
            font-size: 10px; font-weight: 700; color: hsl(var(--primary));
            border: 1px solid hsl(var(--primary) / .35); background: hsl(var(--primary) / .08);
        }
        .portal-lock svg { width: 12px; height: 12px; }
        .portal-card h3 { font-size: 1.05rem; font-weight: 700; }
        .portal-card .blurb { font-size: .78rem; line-height: 1.6; color: hsl(var(--muted-foreground)); }
        .portal-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
        .portal-tag { font-size: 10.5px; font-weight: 600; padding: .15rem .45rem; border-radius: 9999px; background: hsl(var(--muted) / .8); color: hsl(var(--muted-foreground)); }
        .portal-enter { display: flex; align-items: center; gap: .375rem; margin-top: auto; padding-top: .25rem; font-size: .8rem; font-weight: 700; color: hsl(var(--primary)); }
        .portal-enter svg { width: 16px; height: 16px; }
        .portal-foot { font-size: .72rem; color: hsl(var(--muted-foreground)); text-align: center; }
    </style>
    <script>
        // الوضع الداكن هو الأصل: لا يُطفأ إلا إذا اختار المستخدم الفاتح صراحةً.
        if (localStorage.getItem('hawat-theme') !== 'light') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="portal-page">
        <header class="portal-head">
            <a class="hawat-mark" href="{{ route('landing') }}">
                <img class="mark-light" src="{{ asset('images/logo.png') }}" alt="{{ config('hawat.name') }}">
                <img class="mark-dark" src="{{ asset('images/logo-white.png') }}" alt="" aria-hidden="true">
            </a>
        </header>

        <main class="portal-main">
            <div class="portal-groups">
                @foreach ($groups as $group)
                    <section class="portal-group">
                        <h2 class="group-title">{{ $group['title'] }}</h2>
                        <div class="portal-grid">
                            @foreach ($group['portals'] as $entry)
                                <a href="{{ $entry['href'] }}" class="portal-card">
                                    <div class="card-top">
                                        <div class="icon-wrap">@include('partials.icon', ['name' => $entry['icon']])</div>
                                        @if ($entry['guarded'])
                                            <span class="portal-lock" title="بوابة محميّة — تتطلب تسجيل الدخول">
                                                @include('partials.icon', ['name' => 'shield-alert'])
                                                دخول
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <h3>{{ $entry['label'] }}</h3>
                                        <p class="blurb">{{ $entry['blurb'] }}</p>
                                    </div>
                                    <div class="portal-tags">
                                        @foreach ($entry['tags'] as $tag)
                                            <span class="portal-tag">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                    <span class="portal-enter">
                                        الدخول
                                        @include('partials.icon', ['name' => 'chevron-left'])
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </main>

        <footer class="portal-foot">
            جميع الحقوق محفوظة — حوات · مؤسسة دار الحوت للتجارة
        </footer>
    </div>
</body>
</html>
