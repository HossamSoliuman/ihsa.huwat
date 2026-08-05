@props(['title', 'subtitle' => null, 'icon' => 'grid'])

@php
    $icons = [
        'grid' => 'M4 4h7v7H4V4Zm9 0h7v4.5h-7V4ZM4 13h7v7H4v-7Zm9-2.5h7V20h-7v-9.5Z',
        'chart' => 'M4 17.5 9 11l4 3.5L20 6m0 0h-5m5 0v5',
        'bell' => 'M12 3a6 6 0 0 0-6 6c0 4.5-2 6-2 6h16s-2-1.5-2-6a6 6 0 0 0-6-6Zm2.5 15a2.5 2.5 0 0 1-5 0',
    ];
@endphp

<div {{ $attributes->class('info-section-head') }}>
    <span class="info-section-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="{{ $icons[$icon] ?? $icons['grid'] }}"></path></svg>
    </span>
    <div>
        <h2>{{ $title }}</h2>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
</div>
