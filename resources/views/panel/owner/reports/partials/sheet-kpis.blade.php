@if (! empty($kpis))
    <div class="kpis">
        @foreach ($kpis as $kpi)
            <div @class(['strong' => $kpi['strong'] ?? false])>
                <span>{{ $kpi['label'] }}</span>
                <bdi class="num" dir="ltr">{{ \App\Services\Owner\OwnerReports::format($kpi['value'], $kpi['format']) }}</bdi>@if (in_array($kpi['format'], ['money'], true)) <small>ر.س</small>@elseif ($kpi['format'] === 'kg') <small>كجم</small>@endif
            </div>
        @endforeach
    </div>
@endif
