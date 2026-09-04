@php
    use App\Support\Nav;

    /*
     * الصفحة تُقرأ في شاشة واحدة بلا تمرير، فوصف كل بوابة سطر قصير والتبويبات
     * وحدها تفصّل ما بداخلها.
     *
     * الخمس الأولى بوابات لوحة الوزارة، تُقرأ من Nav. والسادسة بوابة المعلومات:
     * ليست منها — لها مضيفها وتخطيطها وقائمتها — فتُبنى هنا من config/info.php،
     * ووسومها مجموعات قائمتها الجانبية. وهي وحدها خلف تسجيل دخول فتحمل علامته.
     */
    $blurbs = [
        Nav::GOV => 'الإنتاج والخريطة البحرية والاستدامة.',
        Nav::STATS => 'المؤشرات والرصد والتحليلات والتقارير.',
        Nav::SUBADMIN => 'الصلاحيات والهيكل والمهام والإنذارات.',
        Nav::SERVICES => 'طلبات الصيادين والرخص والدعم الفني.',
        Nav::OPS => 'القوارب والصيادون والرحلات والموانئ.',
    ];

    $portals = [];

    foreach ($blurbs as $key => $blurb) {
        $portal = Nav::portal($key);

        $portals[] = [
            'label' => $portal['label'],
            'icon' => $portal['icon'],
            'href' => route($portal['home']),
            'blurb' => $blurb,
            'tags' => array_column(Nav::sections($key), 'title'),
            'guarded' => false,
        ];
    }

    $portals[] = [
        'label' => config('info.title'),
        // لا 'settings': ترسها ثمانية أشعة حول دائرة، فتُقرأ شمسًا في هذا القياس.
        'icon' => 'user-cog',
        'href' => route('admin.index'),
        'blurb' => 'البيانات الأساسية والتكاملات وسجل العمليات.',
        'tags' => array_keys(config('info.sidebar')),
        'guarded' => true,
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
        {{-- الصفحة كلها في نافذة واحدة: ترويسة، ثم البوابات، ثم حقوق الوزارة. --}}
        .portal-page {
            height: 100dvh; overflow: hidden;
            display: grid; grid-template-rows: auto 1fr auto; gap: 1.5rem;
            padding: 1.5rem clamp(1rem, 3vw, 2.5rem);
        }
        .portal-head { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 1rem; }
        .hawat-mark img { height: clamp(30px, 4.2vh, 44px); width: auto; display: block; }
        {{-- نسخة الشعار البيضاء للوضع الليلي، والزرقاء للنهاري. --}}
        .hawat-mark .mark-dark { display: none; }
        .dark .hawat-mark .mark-light { display: none; }
        .dark .hawat-mark .mark-dark { display: block; }
        .gov-mark { display: flex; align-items: center; }
        .gov-mark img { height: clamp(52px, 8vh, 84px); width: auto; }
        {{-- شعار الوزارة أخضر داكن، فيُسنَد إلى أرضية بيضاء في الوضع الليلي. --}}
        .dark .gov-mark img { background: #fff; border-radius: .75rem; padding: .5rem .75rem; }

        .portal-main { display: flex; align-items: center; justify-content: center; min-height: 0; }
        .portal-grid { display: grid; gap: 1rem; width: 100%; max-width: 30rem; }
        @media (min-width: 720px) { .portal-grid { grid-template-columns: 1fr 1fr; max-width: 56rem; } }
        {{-- ثلاث في الصفّ على الشاشة المتوسطة، ثم الستّ في صفّ واحد على العريضة. --}}
        @media (min-width: 980px) { .portal-grid { grid-template-columns: repeat(3, 1fr); max-width: 76rem; } }
        @media (min-width: 1400px) { .portal-grid { grid-template-columns: repeat(6, 1fr); max-width: 122rem; } }
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
        .portal-card h2 { font-size: 1.05rem; font-weight: 700; }
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

            <div class="gov-mark">
                <img src="{{ asset('images/moewa-logo.png') }}" alt="{{ config('hawat.ministry') }}">
            </div>

            <span></span>
        </header>

        <main class="portal-main">
            <div class="portal-grid">
                @foreach ($portals as $entry)
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
                            <h2>{{ $entry['label'] }}</h2>
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
        </main>

        <footer class="portal-foot">
            جميع الحقوق محفوظة لوزارة البيئة والمياه والزراعة، وكالة الوزارة لتقنية المعلومات والتحول الرقمي
        </footer>
    </div>
</body>
</html>
