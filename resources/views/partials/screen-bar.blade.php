@php
    use App\Support\Nav;

    /*
     * شريط التحكّم الوحيد في وضع العرض: يعوّض الشريط العلوي والقائمة الجانبية
     * المطويين. على شاشة الاختيار زره الأول يُنهي وضع العرض، وعلى بقية اللوحات
     * يرجع إليها دون مغادرته.
     */
    $home = Nav::portal()['home'];
    $isHome = request()->routeIs($home);
@endphp
<div class="screen-bar">
    @if ($isHome)
        <a href="{{ route($home, ['screen' => 0]) }}" title="إنهاء وضع العرض">
            @include('partials.icon', ['name' => 'x'])
            <span>إنهاء العرض</span>
        </a>
    @else
        <a href="{{ route($home) }}" title="الرجوع إلى شاشة العرض">
            @include('partials.icon', ['name' => 'chevron-right'])
            <span>الشاشات</span>
        </a>
    @endif

    <button type="button" class="fs-btn" onclick="toggleFullscreen()" title="ملء الشاشة">
        <span class="fs-on">@include('partials.icon', ['name' => 'maximize'])</span>
        <span class="fs-off">@include('partials.icon', ['name' => 'minimize'])</span>
    </button>

    <button type="button" onclick="toggleTheme()" title="الوضع الداكن">
        @include('partials.icon', ['name' => 'moon'])
    </button>
</div>
