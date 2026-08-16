<header class="topbar">
    <div class="topbar-inner">
        <a class="topbar-brand" href="{{ route('admin.index') }}">
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" width="389" height="160"
                 alt="{{ config('app.name') }}">
            <span class="brand-text">
                <span class="brand-title">{{ config('info.brand_title', config('info.title')) }}</span>
                <span class="brand-sub">{{ config('info.brand_subtitle') }}</span>
            </span>
        </a>

        {{-- يعود إلى لوحة الوزارة على النطاق الرئيسي، خارج بوابة المعلومات. --}}
        <a class="topbar-ministry" href="{{ route('portal') }}">
            @include('admin.partials.icon', ['name' => 'grid'])
            لوحة الوزارة
        </a>
    </div>
</header>
