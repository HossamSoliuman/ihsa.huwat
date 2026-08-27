@extends('layouts.app')

@section('title', 'شاشة العرض')

@php
    use App\Support\Nav;

    /*
     * المربّعات هي تبويبات القائمة الجانبية نفسها عدا هذه الشاشة، فلا تُذكر
     * اللوحات هنا مرتين: يكفي تعديل nav_gov ليتبدّل الاثنان معًا. السطر الوصفي
     * وحده محلّي — القائمة الجانبية لا تعرضه.
     */
    $blurbs = [
        'gov.overview' => 'المصيد المعتمد والرحلات والأسطول والتنبيهات في لوحة واحدة.',
        'gov.sea-map' => 'مواقع الموانئ ومناطق الصيد وحركة القوارب على الخريطة.',
        'gov.production' => 'الإنتاج السمكي حسب المنطقة والميناء والنوع.',
        'gov.ports-compare' => 'مقارنة أداء الموانئ في الإنتاج والرحلات والالتزام.',
        'gov.sustainability' => 'حالة المخزون السمكي ومؤشرات الاستدامة والصيد العرضي.',
    ];

    $screen = Nav::screenMode();
    $tiles = collect(Nav::sections())
        ->flatMap(fn (array $section) => array_map(
            fn (array $item) => $item + ['group' => $section['title']],
            $section['items'],
        ))
        ->reject(fn (array $item) => request()->routeIs($item['route']));
@endphp

@section('content')
    <div class="screen-launcher">
        <div class="screen-launcher-head">
            <div>
                <p class="kicker">{{ config('hawat.sector') }}</p>
                <h1>{{ config('hawat.tagline') }}</h1>
            </div>

            {{-- الشاشة على وضع العرض افتراضًا، وهذا الزر لا يظهر إلا بعد الخروج منه. --}}
            @unless ($screen)
                <a href="{{ route('gov.home') }}" class="btn btn-outline" data-screen-tile>
                    @include('partials.icon', ['name' => 'maximize'])
                    وضع العرض
                </a>
            @endunless
        </div>

        <div class="screen-grid">
            @foreach ($tiles as $tile)
                <a href="{{ route($tile['route'], ['screen' => 1]) }}" class="screen-tile" data-screen-tile>
                    <div class="t-top">
                        <div class="t-icon">@include('partials.icon', ['name' => $tile['icon']])</div>
                        <span class="t-group">{{ $tile['group'] }}</span>
                    </div>
                    <div>
                        <h2>{{ $tile['label'] }}</h2>
                        <p>{{ $blurbs[$tile['route']] ?? '' }}</p>
                    </div>
                    <span class="t-go">
                        عرض بملء الشاشة
                        @include('partials.icon', ['name' => 'chevron-left'])
                    </span>
                </a>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // ملء شاشة المتصفح لا يُطلب إلا داخل إيماءة مستخدم، ونقر المربّع هو الإيماءة
    // الوحيدة قبل فتح اللوحة: نطلبه أولًا ثم ننتقل، ولا نُعطّل النقرة إن رُفض.
    document.querySelectorAll('[data-screen-tile]').forEach(function (tile) {
        tile.addEventListener('click', function (event) {
            if (document.fullscreenElement || !document.documentElement.requestFullscreen) {
                return;
            }

            event.preventDefault();
            document.documentElement.requestFullscreen()
                .catch(function () {})
                .finally(function () { window.location.href = tile.href; });
        });
    });
</script>
@endpush
