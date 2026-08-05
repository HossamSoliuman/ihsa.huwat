@props(['series' => [], 'empty' => 'لا توجد بيانات ضمن الفترة المحددة.'])

@php
    $pointCount = count($series[0]['values'] ?? []);
    $maximum = max(1, (float) collect($series)->flatMap(fn ($item) => collect($item['values'])->pluck('value'))->max());
    $plotWidth = 640;
@endphp

@if ($pointCount === 0 || collect($series)->flatMap(fn ($item) => $item['values'])->sum('value') === 0)
    <p class="info-chart-empty">{{ $empty }}</p>
@else
    <svg {{ $attributes->class('info-chart-line') }} viewBox="0 0 720 260" role="img" aria-label="رسم خطي زمني">
        @foreach ([0, 0.25, 0.5, 0.75, 1] as $gridStep)
            @php $gridY = 220 - ($gridStep * 180); @endphp
            <line class="info-chart-gridline" x1="40" x2="680" y1="{{ $gridY }}" y2="{{ $gridY }}"></line>
        @endforeach
        <line class="info-chart-crosshair" x1="0" x2="0" y1="40" y2="220" hidden data-chart-crosshair></line>

        @foreach ($series as $seriesItem)
            @php
                $points = collect($seriesItem['values'])->map(function ($point, $index) use ($pointCount, $plotWidth, $maximum) {
                    $x = $pointCount <= 1 ? 360 : 680 - (($index / ($pointCount - 1)) * $plotWidth);
                    $y = 220 - (($point['value'] / $maximum) * 180);

                    return ['x' => $x, 'y' => $y, ...$point];
                });
            @endphp
            <polyline class="tone-{{ $seriesItem['tone'] }}" points="{{ $points->map(fn ($point) => round($point['x'], 2).','.round($point['y'], 2))->implode(' ') }}"></polyline>
            @foreach ($points as $index => $point)
                <circle class="tone-{{ $seriesItem['tone'] }}" cx="{{ round($point['x'], 2) }}" cy="{{ round($point['y'], 2) }}" r="{{ $index === $pointCount - 1 ? 5 : 4 }}"
                        tabindex="0" data-chart-point data-chart-x="{{ round($point['x'], 2) }}"
                        data-tooltip="{{ $seriesItem['label'] }} · {{ $point['label'] }}: {{ $point['value'] }}"></circle>
            @endforeach
        @endforeach

        @foreach (($series[0]['values'] ?? []) as $index => $point)
            @if ($index === 0 || $index === $pointCount - 1 || ($pointCount > 8 && $index % (int) ceil($pointCount / 6) === 0))
                @php $x = $pointCount <= 1 ? 360 : 680 - (($index / ($pointCount - 1)) * $plotWidth); @endphp
                <text class="info-chart-axis-label" x="{{ round($x, 2) }}" y="246">{{ $point['label'] }}</text>
            @endif
        @endforeach
    </svg>
@endif
