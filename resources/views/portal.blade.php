@php
    use App\Support\Nav;

    $portals = [
        [
            'portal' => Nav::portal(Nav::GOV),
            'blurb' => 'الإنتاج السمكي، الخريطة البحرية، الاستدامة والمخزون، والرقابة والامتثال.',
            'sections' => Nav::sections(Nav::GOV),
        ],
        [
            'portal' => Nav::portal(Nav::STATS),
            'blurb' => 'المؤشرات الوطنية، الرصد الميداني والاعتماد، التحليلات والتقارير، والأسواق والأمن الغذائي.',
            'sections' => Nav::sections(Nav::STATS),
        ],
        [
            'portal' => Nav::portal(Nav::SUBADMIN),
            'blurb' => 'مركز الإدارة والصلاحيات، الهيكل التنظيمي، المهام والتنبيهات، سجل العمليات والإنذارات.',
            'sections' => Nav::sections(Nav::SUBADMIN),
        ],
        [
            'portal' => Nav::portal(Nav::SERVICES),
            'blurb' => 'طلبات الصيادين ومعالجتها، موظفو الخدمات وصلاحياتهم، رخص المواسم والرقابة، والدعم الفني.',
            'sections' => Nav::sections(Nav::SERVICES),
        ],
        [
            'portal' => Nav::portal(Nav::OPS),
            'blurb' => 'سجلات القوارب والصيادين والرحلات، الموانئ، مواسم الصيد، ومراجعة الفروقات.',
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
        .portal-page { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2.5rem; padding: 3rem 1.5rem; }
        .portal-brand { display: flex; justify-content: center; }
        .portal-brand img { height: 200px; width: auto; max-width: 100%; object-fit: contain; }
        .portal-grid { display: grid; gap: 1.25rem; width: 100%; max-width: 30rem; }
        {{-- البوابات الخمس في صفّ واحد على الشاشة العريضة، وتتكدّس دونها. --}}
        @media (min-width: 720px) { .portal-grid { grid-template-columns: 1fr 1fr; max-width: 56rem; } }
        @media (min-width: 1100px) { .portal-grid { grid-template-columns: repeat(5, 1fr); max-width: 108rem; } }
        .portal-card {
            display: flex; flex-direction: column; gap: 1rem; padding: 1.75rem;
            border: 1px solid hsl(var(--border)); border-radius: calc(var(--radius) * 2);
            background: hsl(var(--card));
            transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
        }
        .portal-card:hover { border-color: hsl(var(--primary) / .5); transform: translateY(-2px); box-shadow: 0 12px 32px hsl(var(--primary) / .12); }
        .portal-card .icon-wrap {
            display: flex; align-items: center; justify-content: center; height: 3rem; width: 3rem;
            border-radius: .875rem; background: hsl(var(--primary) / .12); color: hsl(var(--primary));
        }
        .portal-card .icon-wrap svg { width: 24px; height: 24px; }
        .portal-card h2 { font-size: 1.15rem; font-weight: 700; }
        .portal-card .blurb { font-size: .82rem; line-height: 1.7; color: hsl(var(--muted-foreground)); }
        .portal-tags { display: flex; flex-wrap: wrap; gap: .375rem; }
        .portal-tag { font-size: 11px; font-weight: 600; padding: .2rem .5rem; border-radius: 9999px; background: hsl(var(--muted) / .8); color: hsl(var(--muted-foreground)); }
        .portal-enter { display: flex; align-items: center; gap: .375rem; margin-top: auto; padding-top: .5rem; font-size: .82rem; font-weight: 700; color: hsl(var(--primary)); }
        .portal-enter svg { width: 16px; height: 16px; }
        .portal-foot { font-size: .75rem; color: hsl(var(--muted-foreground)); text-align: center; }
    </style>
    <script>
        if (localStorage.getItem('hawat-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body>
    <div class="portal-page">
        <div class="portal-brand">
            <img src="{{ config('hawat.logo') }}" alt="{{ config('hawat.name') }}">
        </div>

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

        <p class="portal-foot">{{ config('hawat.ministry') }} — {{ config('hawat.sector') }}</p>
    </div>
</body>
</html>
