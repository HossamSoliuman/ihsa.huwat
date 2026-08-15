@php
    $value = data_get($record, $column);
    $tone = $badgeMap[$column][$value] ?? null;

    if (is_bool($value)) {
        $display = $value ? 'نعم' : 'لا';
    } elseif ($value instanceof \DateTimeInterface) {
        $display = $value->format('Y-m-d');
    } elseif (is_numeric($value) && ! in_array($column, ['code', 'lat', 'lng'], true)) {
        $display = number_format((float) $value, fmod((float) $value, 1) === 0.0 ? 0 : 2);
    } else {
        $display = $value;
    }

    $display = ($display === null || $display === '') ? '—' : $display;
@endphp

@if ($tone)
    <span class="badge badge-{{ $tone }}">{{ $display }}</span>
@else
    {{ $display }}
@endif