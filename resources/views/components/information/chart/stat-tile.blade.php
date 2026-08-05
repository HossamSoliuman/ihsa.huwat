@props([
    'label',
    'value' => null,
    'suffix' => null,
    'delta' => null,
    'deltaGood' => true,
    'deltaSuffix' => '%',
    'href' => null,
    'icon' => 'inbox',
    'tone' => null,
])

@php
    $icons = [
        'inbox' => 'M3 13h5l1.5 2.5h5L16 13h5M4.5 5h15l1.5 8v6H3v-6l1.5-8Z',
        'clock' => 'M12 7.5V12l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'alert' => 'M12 9.5V13m0 3h.01M10.6 4.4 2.9 17.7a1.6 1.6 0 0 0 1.4 2.4h15.4a1.6 1.6 0 0 0 1.4-2.4L13.4 4.4a1.6 1.6 0 0 0-2.8 0Z',
        'hourglass' => 'M7 3h10M7 21h10M8 3v3.6c0 2 4 3.4 4 5.4s-4 3.4-4 5.4V21M16 3v3.6c0 2-4 3.4-4 5.4s4 3.4 4 5.4V21',
        'gauge' => 'M4 19a9 9 0 1 1 16 0M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm1.6-3.6L17.5 7',
        'check' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3.5-9.2 2.5 2.6 4.5-4.8',
        'repeat' => 'M4 9V8a3 3 0 0 1 3-3h13m0 0-3-3m3 3-3 3M20 15v1a3 3 0 0 1-3 3H4m0 0 3 3m-3-3 3-3',
        'shield' => 'M12 3.2 19.5 6v5.7c0 4.3-3.1 7.6-7.5 8.6-4.4-1-7.5-4.3-7.5-8.6V6L12 3.2Zm0 5.3v3.6m0 3.1h.01',
    ];

    $deltaTone = 'neutral';
    if ($delta !== null && (float) $delta !== 0.0) {
        $isImprovement = $deltaGood ? $delta > 0 : $delta < 0;
        $deltaTone = $isImprovement ? 'good' : 'bad';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('info-chart-stat') }} @if ($tone) data-tone="{{ $tone }}" @endif>
@else
    <article {{ $attributes->class('info-chart-stat') }} @if ($tone) data-tone="{{ $tone }}" @endif>
@endif
        <span class="info-chart-stat-head">
            <span class="info-chart-stat-label">{{ $label }}</span>
            <span class="info-chart-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="{{ $icons[$icon] ?? $icons['inbox'] }}"></path></svg>
            </span>
        </span>

        <span class="info-chart-stat-foot">
            <span class="info-chart-stat-value">
                {{ $value === null ? '—' : number_format((float) $value, is_float($value) ? 1 : 0) }}
                @if ($value !== null && $suffix)
                    <small>{{ $suffix }}</small>
                @endif
            </span>
            @if ($delta !== null)
                <span class="info-chart-delta" data-tone="{{ $deltaTone }}">
                    {{ $delta > 0 ? '↑' : ($delta < 0 ? '↓' : '•') }}{{ number_format(abs($delta), 1) }} {{ $deltaSuffix }}
                    <span class="sr-only">مقارنة بالفترة السابقة</span>
                </span>
            @endif
        </span>
@if ($href)
    </a>
@else
    </article>
@endif
