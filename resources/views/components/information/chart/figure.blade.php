@props(['id', 'title', 'subtitle' => null, 'icon' => null, 'legend' => []])

@php
    $icons = [
        'trend' => 'M4 17.5 9 11l4 3.5L20 6m0 0h-5m5 0v5',
        'layers' => 'm12 3 8 4.5-8 4.5-8-4.5L12 3Zm8 9-8 4.5L4 12m16 4.5L12 21l-8-4.5',
        'users' => 'M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6 9c0-3 2.7-5 6-5s6 2 6 5m1.5-14a3.5 3.5 0 0 1 0 7M17 15c2.4.5 4 2.2 4 5',
        'anchor' => 'M12 8.5V21m0 0a8 8 0 0 1-8-8m8 8a8 8 0 0 0 8-8m-8-7.5a2 2 0 1 0 0 4 2 2 0 0 0 0-4ZM8.5 11h7',
        'clock' => 'M12 7.5V12l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'shield' => 'M12 3.2 19.5 6v5.7c0 4.3-3.1 7.6-7.5 8.6-4.4-1-7.5-4.3-7.5-8.6V6L12 3.2Zm0 5.3v3.6m0 3.1h.01',
        'pulse' => 'M3 12h3.5L9 5l4 14 2.5-7H21',
    ];
@endphp

<figure {{ $attributes->class('info-chart-figure') }} aria-labelledby="{{ $id }}-title">
    <figcaption class="info-chart-head">
        <div>
            <h2 id="{{ $id }}-title">
                @if ($icon && isset($icons[$icon]))
                    <svg class="info-chart-head-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icons[$icon] }}"></path></svg>
                @endif
                <span>{{ $title }}</span>
            </h2>
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>
        @isset($action)
            {{ $action }}
        @endisset
    </figcaption>

    <x-information.chart.legend :items="$legend" />

    <div class="info-chart-canvas">
        {{ $slot }}
    </div>

    @isset($table)
        <details class="info-chart-details" data-chart-details>
            <summary>تفاصيل البيانات</summary>
            <div class="info-chart-table-scroll">
                {{ $table }}
            </div>
        </details>
    @endisset
</figure>
