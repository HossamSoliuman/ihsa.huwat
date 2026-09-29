{{-- خلية تقرير بصيغة عمودها: الأرقام بخط الأرقام، والرمز والتاريخ معزولان حتى لا تنقلب شرطاتهما بجوار العربية. --}}
@php
    $value = $row[$column['key']] ?? null;
    $text = \App\Services\Owner\OwnerReports::format($value, $column['format']);
    $isNum = \App\Services\Owner\OwnerReports::isNumeric($column['format']);
    $classes = trim(($isNum ? 'num' : '').($column['strong'] ? ' strong' : '').($isNum && is_numeric($value) && $value < 0 ? ' neg' : ''));
@endphp
<td class="{{ $classes }}" @if ($column['strong'] && ($web ?? false)) style="font-weight:700" @endif>
    @if ($value !== null && $value !== '' && in_array($column['format'], ['code', 'date'], true))
        @if (($web ?? false) && isset($row['_url']) && ($first ?? false))
            <a href="{{ $row['_url'] }}"><bdi class="num" dir="ltr">{{ $text }}</bdi></a>
        @else
            <bdi class="num" dir="ltr">{{ $text }}</bdi>
        @endif
    @elseif (($web ?? false) && isset($row['_url']) && ($first ?? false))
        <a href="{{ $row['_url'] }}">{{ $text }}</a>
    @elseif ($isNum)
        <bdi dir="ltr">{{ $text }}</bdi>
    @else
        {{ $text }}
    @endif
</td>
