@php
    $allTabs = config('hawat.tabs', []);
    $groups = [];
    $placed = [];

    foreach (config('hawat.sidebar', []) as $group => $keys) {
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
@endphp

<aside class="sidebar">
    <nav class="sidebar-nav">
        @foreach ($groups as $group => $keys)
            <div class="sidebar-group">{{ $group }}</div>
            @foreach ($keys as $key)
                <a class="sidebar-link @if ($key === $activeTab) is-active @endif"
                   href="{{ route('admin.tab', $key) }}"
                   @if ($key === $activeTab) aria-current="page" @endif>
                    <span>{{ $allTabs[$key]['label'] }}</span>
                    @include('admin.partials.icon', ['name' => $allTabs[$key]['icon']])
                </a>
            @endforeach
        @endforeach
    </nav>
</aside>
