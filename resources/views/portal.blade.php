@php
    use App\Support\Nav;

    /*
     * الصفحة تُقرأ في شاشة واحدة بلا تمرير، فوصف كل بوابة سطر قصير والتبويبات
     * وحدها تفصّل ما بداخلها.
     */
    $portals = [
        [
            'portal' => Nav::portal(Nav::GOV),
            'blurb' => 'الإنتاج والخريطة البحرية والاستدامة.',
            'sections' => Nav::sections(Nav::GOV),
        ],
        [
            'portal' => Nav::portal(Nav::STATS),
            'blurb' => 'المؤشرات والرصد والتحليلات والتقارير.',
            'sections' => Nav::sections(Nav::STATS),
        ],
        [
            'portal' => Nav::portal(Nav::SUBADMIN),
            'blurb' => 'الصلاحيات والهيكل والمهام والإنذارات.',
            'sections' => Nav::sections(Nav::SUBADMIN),
        ],
        [
            'portal' => Nav::portal(Nav::SERVICES),
            'blurb' => 'طلبات الصيادين والرخص والدعم الفني.',
            'sections' => Nav::sections(Nav::SERVICES),
        ],
        [
            'portal' => Nav::portal(Nav::OPS),
            'blurb' => 'القوارب والصيادون والرحلات والموانئ.',
            'sections' => Nav::sections(Nav::OPS),
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
        .gov-mark { display: flex; flex-direction: column; align-items: center; gap: .5rem; }
        .gov-mark img { height: clamp(52px, 8vh, 84px); width: auto; }
        .gov-mark .sector { font-size: .78rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
        {{-- شعار الوزارة أخضر داكن، فيُسنَد إلى أرضية بيضاء في الوضع الليلي. --}}
        .dark .gov-mark img { background: #fff; border-radius: .75rem; padding: .5rem .75rem; }

        .portal-main { display: flex; align-items: center; justify-content: center; min-height: 0; }
        .portal-grid { display: grid; gap: 1rem; width: 100%; max-width: 30rem; }
        @media (min-width: 720px) { .portal-grid { grid-template-columns: 1fr 1fr; max-width: 56rem; } }
        {{-- البوابات الخمس في صفّ واحد على الشاشة العريضة، وتتكدّس دونها. --}}
        @media (min-width: 1100px) { .portal-grid { grid-template-columns: repeat(5, 1fr); max-width: 108rem; } }
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
        .portal-card .icon-wrap {
            display: flex; align-items: center; justify-content: center; height: 2.5rem; width: 2.5rem;
            border-radius: .75rem; background: hsl(var(--primary) / .12); color: hsl(var(--primary));
        }
        .portal-card .icon-wrap svg { width: 20px; height: 20px; }
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
                <span class="sector">{{ config('hawat.sector') }}</span>
            </div>

            <span></span>
        </header>

        <main class="portal-main">
            <div class="portal-grid">
                @foreach ($portals as $entry)
                    <a href="{{ route($entry['portal']['home']) }}" class="portal-card">
                        <div class="icon-wrap">@include('partials.icon', ['name' => $entry['portal']['icon']])</div>
                        <div>
                            <h2>{{ $entry['portal']['label'] }}</h2>
                            <p class="blurb">{{ $entry['blurb'] }}</p>
                        </div>
                        <div class="portal-tags">
                            @foreach ($entry['sections'] as $section)
                                <span class="portal-tag">{{ $section['title'] }}</span>
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
