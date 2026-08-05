@props(['items' => [], 'empty' => 'لا توجد بيانات ضمن الفترة المحددة.'])

@php $maximum = max(1, (int) collect($items)->max('value')); @endphp

@if (count($items) === 0)
    <p class="info-chart-empty">{{ $empty }}</p>
@else
    <ol {{ $attributes->class('info-chart-bar-list') }}>
        @foreach ($items as $item)
            <li>
                <div class="info-chart-bar-meta">
                    @if ($item['href'] ?? null)
                        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                    @else
                        <span>{{ $item['label'] }}</span>
                    @endif
                    <b>{{ number_format($item['value']) }}</b>
                </div>
                <progress class="tone-{{ $item['tone'] ?? 'slot-1' }}" max="{{ $maximum }}" value="{{ $item['value'] }}"
                          data-chart-mark data-tooltip="{{ $item['label'] }}: {{ $item['value'] }}"></progress>
            </li>
        @endforeach
    </ol>
@endif
