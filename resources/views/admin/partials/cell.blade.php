@php
    // أعمدة تحمل أرقامًا لكنها معرّفات لا مقادير — تُعرض كما هي بلا فواصل آلاف.
    $identifierColumns = [
        'code', 'lat', 'lng', 'national_id', 'employee_number', 'boat_number',
        'license_number', 'phone', 'fao_code', 'local_id', 'record_id', 'run_id',
        'start_month', 'end_month', 'decision_number',
    ];

    $value = data_get($record, $column);
    $tone = $badgeMap[$column][$value] ?? null;

    if (is_bool($value)) {
        $display = $value ? 'نعم' : 'لا';
    } elseif ($value instanceof \DateTimeInterface) {
        $display = $value->format($value->format('His') === '000000' ? 'Y-m-d' : 'Y-m-d H:i');
    } elseif (is_numeric($value) && ! in_array($column, $identifierColumns, true)) {
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