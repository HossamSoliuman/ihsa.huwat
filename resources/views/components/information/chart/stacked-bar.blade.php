@props(['bars' => [], 'empty' => 'لا توجد بيانات ضمن الفترة المحددة.'])

@if (count($bars) === 0 || collect($bars)->sum(fn ($bar) => collect($bar['segments'])->sum('value')) === 0)
    <p class="info-chart-empty">{{ $empty }}</p>
@else
    <div {{ $attributes->class('info-chart-stacked-list') }}>
        @foreach ($bars as $bar)
            @php
                $total = max(1, (int) collect($bar['segments'])->sum('value'));
                $cursor = 100.0;
            @endphp
            <div class="info-chart-stacked-row">
                <span>{{ $bar['label'] }}</span>
                <svg viewBox="0 0 600 24" role="img" aria-label="{{ $bar['label'] }}، الإجمالي {{ $total }}">
                    @foreach ($bar['segments'] as $segment)
                        @php
                            $width = ((int) $segment['value'] / $total) * 100;
                            $cursor -= $width;
                            $visibleX = $cursor + 0.2;
                            $visibleWidth = max(0, $width - 0.4);
                        @endphp
                        @if ($visibleWidth > 0)
                            @if ($segment['href'] ?? null)<a href="{{ $segment['href'] }}">@endif
                                <rect class="tone-{{ $segment['tone'] }}" x="{{ round($visibleX * 6, 3) }}" y="5" width="{{ round($visibleWidth * 6, 3) }}" height="14" rx="2"
                                      data-chart-mark data-tooltip="{{ $segment['label'] }}: {{ $segment['value'] }}"></rect>
                                @if ($width >= 12)
                                    <text x="{{ round(($cursor + ($width / 2)) * 6, 3) }}" y="14.3">{{ $segment['value'] }}</text>
                                @endif
                            @if ($segment['href'] ?? null)</a>@endif
                        @endif
                    @endforeach
                </svg>
            </div>
        @endforeach
    </div>
@endif
