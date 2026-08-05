@props(['items' => []])

@if (count($items) > 1)
    <ul {{ $attributes->class('info-chart-legend') }} aria-label="مفتاح الرسم">
        @foreach ($items as $item)
            <li><i class="info-chart-swatch tone-{{ $item['tone'] }}" aria-hidden="true"></i>{{ $item['label'] }}</li>
        @endforeach
    </ul>
@endif
