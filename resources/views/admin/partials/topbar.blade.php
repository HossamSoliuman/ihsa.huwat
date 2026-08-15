<header class="topbar">
    <div class="topbar-inner">
        <a class="topbar-brand" href="{{ route('admin.index') }}">
            <span class="brand-text">
                <span class="brand-title">{{ config('hawat.brand_title', config('hawat.title')) }}</span>
                <span class="brand-sub">{{ config('hawat.brand_subtitle') }}</span>
            </span>
            <span class="brand-mark">@include('admin.partials.icon', ['name' => 'shield'])</span>
        </a>

        <a class="topbar-ministry" href="{{ route('admin.index') }}">
            @include('admin.partials.icon', ['name' => 'grid'])
            لوحة الوزارة
        </a>
    </div>
</header>
