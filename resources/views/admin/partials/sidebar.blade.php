@php
    $allTabs = config('info.tabs', []);
    $groups = [];
    $placed = [];

    foreach (config('info.sidebar', []) as $group => $keys) {
        $items = array_values(array_intersect(array_unique($keys), array_keys($allTabs)));

        if ($items) {
            $groups[$group] = $items;
            $placed = array_merge($placed, $items);
        }
    }

    // أي تبويب غير مُدرج في مجموعة يظهر هنا حتى لا يختفي من القائمة.
    if ($rest = array_values(array_diff(array_keys($allTabs), $placed))) {
        $groups['أخرى'] = $rest;
    }

    $user = auth()->user();
@endphp

<aside class="sidebar" id="sidebar">
    {{-- الشعار في الشريط العلوي، فلم يبقَ في رأس القائمة إلا زرّ الإغلاق — ولا يظهر إلا دون 1024px. --}}
    <div class="sidebar-head">
        <button class="sidebar-close" onclick="toggleSidebar(false)" aria-label="إغلاق القائمة">
            @include('admin.partials.icon', ['name' => 'close'])
        </button>
    </div>

    <nav class="sidebar-nav">
        @foreach ($groups as $group => $keys)
            <div class="nav-section">
                <p class="nav-section-title">{{ $group }}</p>
                @foreach ($keys as $key)
                    <a class="nav-link {{ $key === $activeTab ? 'is-active' : '' }}"
                       href="{{ route('admin.tab', $key) }}"
                       @if ($key === $activeTab) aria-current="page" @endif>
                        @include('admin.partials.icon', ['name' => $allTabs[$key]['icon']])
                        <span>{{ $allTabs[$key]['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- ذيل القائمة: هويّة من دخل — وهي نفسها التي تُنسب إليها كتابات سجل العمليات. --}}
    <div class="sidebar-foot">
        <div class="user-chip">
            <div class="avatar">{{ mb_substr($user?->name ?? '؟', 0, 1) }}</div>
            <div class="meta">
                <p class="role">{{ $user?->name }}</p>
                <p class="sub">{{ $user?->role_label }}</p>
            </div>
        </div>
    </div>
</aside>
